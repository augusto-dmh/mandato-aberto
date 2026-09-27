"""Roll calls, votes, exercise periods and the four indicators, ported from the prototype `etl/build.py`.

Every indicator is a `{count, total}` pair; nothing here computes a percentage (AD-004).
Câmara timestamps are Brasília local time without an offset and are compared as such.
"""

import unicodedata
from collections import Counter, defaultdict
from collections.abc import Callable, Iterable
from dataclasses import dataclass, field

LEGISLATURE = "57"
LEGISLATURE_START = "2023-02-01"
PLENARY = "PLEN"
VALID_VOTES = {"Sim", "Não", "Abstenção", "Obstrução"}
AUTHORED_TYPES = {"PL", "PLP", "PEC", "PDL", "PRC"}
REQUIREMENT_TYPES = {"REQ", "RIC", "INC"}

DEPUTY_URL = "https://www.camara.leg.br/deputados/{id}"
ROLL_CALL_URL = "https://dadosabertos.camara.leg.br/api/v2/votacoes/{id}"
PROPOSITION_URL = "https://www.camara.leg.br/propostas-legislativas/{id}"


def seconds(timestamp: str) -> str:
    """Normalizes `2023-02-01`, `2023-02-01T12:05` and `2023-02-01T12:05:00` to the last form."""
    if len(timestamp) == 10:
        return timestamp + "T00:00:00"
    if len(timestamp) == 16:
        return timestamp + ":00"
    return timestamp[:19]


def sort_name(name: str) -> str:
    decomposed = unicodedata.normalize("NFKD", name)
    return "".join(c for c in decomposed if not unicodedata.combining(c)).casefold()


def exercise_periods(history: Iterable[dict], end: str) -> list[dict]:
    """Half-open `[start, end)` intervals in which the status was `Exercício`.

    Entries before the legislature are ignored: some carry the current legislature id for events of
    the previous one. The last open period closes at `end`, the build time.
    """
    periods, start = [], None
    for h in sorted((h for h in history if h["dataHora"] >= LEGISLATURE_START), key=lambda h: h["dataHora"]):
        at = seconds(h["dataHora"])
        if h["situacao"] == "Exercício":
            if start is None:
                start = at
        elif start is not None:
            periods.append({"start": start, "end": at})
            start = None
    if start is not None:
        periods.append({"start": start, "end": end})
    return periods


def in_periods(at: str, periods: list[dict]) -> bool:
    return any(p["start"] <= at < p["end"] for p in periods)


def party_majority(counts: Counter, own_vote: str) -> str | None:
    """Most frequent valid vote of the other party members; `None` on a tie or when nobody else voted."""
    others = Counter(counts)
    if own_vote in others:
        others[own_vote] -= 1
    top = [(vote, n) for vote, n in others.most_common() if n > 0][:2]
    if not top or (len(top) == 2 and top[0][1] == top[1][1]):
        return None
    return top[0][0]


def indicator(count: int, total: int) -> dict:
    return {"count": count, "total": total}


@dataclass
class Loaded:
    roll_calls: dict[str, dict] = field(default_factory=dict)
    # (rollCallId, deputyId) -> (dataHoraVoto, vote, party); the latest record wins
    votes: dict[tuple[str, str], tuple[str, str, str]] = field(default_factory=dict)
    # deputyId -> the vote row with the latest dataHoraVoto
    profiles: dict[str, dict] = field(default_factory=dict)
    # deputyId -> {propositionId: firstSigner}
    authorship: dict[str, dict[str, bool]] = field(default_factory=lambda: defaultdict(dict))
    propositions: dict[str, dict] = field(default_factory=dict)
    requirements: set[str] = field(default_factory=set)


def load(read: Callable[[str], Iterable[dict]]) -> Loaded:
    """Reads every Câmara source through `read(kind)` into the records the indicators need."""
    data = Loaded()
    for r in read("votacoes"):
        if r["data"] < LEGISLATURE_START:
            continue
        data.roll_calls[r["id"]] = {
            "id": r["id"],
            "date": r["data"],
            "at": seconds(r["dataHoraRegistro"]),
            "organ": r["siglaOrgao"],
            "description": r["descricao"].strip(),
            "approved": {"1": True, "0": False}.get(r["aprovacao"]),
            "proposition": None,
            "governmentOrientation": None,
        }

    for r in read("votacoesProposicoes"):
        rc = data.roll_calls.get(r["idVotacao"])
        if rc and rc["proposition"] is None and r["proposicao_id"]:
            rc["proposition"] = {
                "id": int(r["proposicao_id"]),
                "title": r["proposicao_titulo"].strip(),
                "summary": r["proposicao_ementa"].strip() or None,
            }

    for r in read("votacoesOrientacoes"):
        rc = data.roll_calls.get(r["idVotacao"])
        if rc and r["siglaBancada"].strip().casefold() == "governo" and r["orientacao"]:
            rc["governmentOrientation"] = r["orientacao"]

    for r in read("votacoesVotos"):
        if r["deputado_idLegislatura"] != LEGISLATURE:
            continue
        dep = r["deputado_id"]
        profile = data.profiles.get(dep)
        if profile is None or r["dataHoraVoto"] >= profile["dataHoraVoto"]:
            data.profiles[dep] = r
        if r["idVotacao"] not in data.roll_calls:
            continue
        key = (r["idVotacao"], dep)
        previous = data.votes.get(key)
        if previous is None or r["dataHoraVoto"] >= previous[0]:
            data.votes[key] = (r["dataHoraVoto"], r["voto"], r["deputado_siglaPartido"])

    voted = {rc for rc, _ in data.votes}
    data.roll_calls = {rc: v for rc, v in data.roll_calls.items() if rc in voted}

    for r in read("proposicoesAutores"):
        dep = r["idDeputadoAutor"]
        if dep in data.profiles and r["proponente"] == "1":
            prop = r["idProposicao"]
            data.authorship[dep][prop] = data.authorship[dep].get(prop, False) or r["ordemAssinatura"] == "1"

    wanted = {prop for props in data.authorship.values() for prop in props}
    for r in read("proposicoes"):
        if r["id"] not in wanted or r["dataApresentacao"][:10] < LEGISLATURE_START:
            continue
        if r["siglaTipo"] in AUTHORED_TYPES:
            data.propositions[r["id"]] = {
                "id": int(r["id"]),
                "type": r["siglaTipo"],
                "number": int(r["numero"]),
                "year": int(r["ano"]),
                "summary": r["ementa"].strip(),
                "presentedAt": r["dataApresentacao"][:10],
                "status": r["ultimoStatus_descricaoSituacao"].strip() or None,
                "sourceUrl": PROPOSITION_URL.format(id=r["id"]),
            }
        elif r["siglaTipo"] in REQUIREMENT_TYPES:
            data.requirements.add(r["id"])
    return data


def _roll_call_summary(rc: dict, votes: dict[str, tuple[str, str]]) -> dict:
    values = [vote for vote, _ in votes.values()]
    yes, no = values.count("Sim"), values.count("Não")
    return {
        "id": rc["id"],
        "date": rc["date"],
        "organ": rc["organ"],
        "description": rc["description"],
        "proposition": rc["proposition"],
        "approved": rc["approved"],
        "tallies": {"yes": yes, "no": no, "others": sum(1 for v in values if v) - yes - no},
        "governmentOrientation": rc["governmentOrientation"],
        "sourceUrl": ROLL_CALL_URL.format(id=rc["id"]),
    }


def assemble(
    data: Loaded,
    histories: dict[str, list[dict]],
    in_exercise: set[str],
    candidacies: dict[str, dict],
    now_local: str,
) -> dict:
    """Builds every record of the contract, keyed by kind: deputies, deputy docs, roll calls, roll-call docs."""
    by_roll_call: dict[str, dict[str, tuple[str, str]]] = defaultdict(dict)
    by_deputy: dict[str, list[tuple[str, str, str]]] = defaultdict(list)
    party_counts: dict[tuple[str, str], Counter] = defaultdict(Counter)
    for (rc, dep), (_, vote, party) in data.votes.items():
        by_roll_call[rc][dep] = (vote, party)
        by_deputy[dep].append((rc, vote, party))
        if vote in VALID_VOTES:
            party_counts[(rc, party)][vote] += 1

    roll_call_docs = {}
    for rc_id, rc in data.roll_calls.items():
        doc = _roll_call_summary(rc, by_roll_call[rc_id])
        doc["votes"] = [
            {"deputyId": int(dep), "vote": vote, "party": party}
            for dep, (vote, party) in sorted(by_roll_call[rc_id].items(), key=lambda item: int(item[0]))
        ]
        roll_call_docs[rc_id] = doc

    ordered = sorted(data.roll_calls.values(), key=lambda rc: rc["id"])
    ordered.sort(key=lambda rc: rc["date"], reverse=True)
    roll_calls = [{k: v for k, v in roll_call_docs[rc["id"]].items() if k != "votes"} for rc in ordered]
    plenary = [rc for rc in data.roll_calls.values() if rc["organ"] == PLENARY]

    deputies, deputy_docs = [], {}
    for dep, profile in data.profiles.items():
        periods = exercise_periods(histories.get(dep, []), now_local)
        eligible = [rc["id"] for rc in plenary if in_periods(rc["at"], periods)]
        recorded = sum(1 for rc in eligible if by_roll_call[rc].get(dep, ("", ""))[0])

        gov_count = gov_total = party_count = party_total = 0
        votes = []
        # date descending, id ascending within a date: two stable sorts
        own = sorted(by_deputy[dep])
        own.sort(key=lambda v: data.roll_calls[v[0]]["date"], reverse=True)
        for rc, vote, party in own:
            majority = party_majority(party_counts[(rc, party)], vote)
            orientation = data.roll_calls[rc]["governmentOrientation"]
            if vote in VALID_VOTES:
                if orientation in VALID_VOTES:
                    gov_total += 1
                    gov_count += vote == orientation
                if majority is not None:
                    party_total += 1
                    party_count += vote == majority
            votes.append({"rollCallId": rc, "vote": vote, "party": party, "partyMajority": majority})

        authored = sorted(
            ({**data.propositions[p], "firstSigner": first} for p, first in data.authorship[dep].items() if p in data.propositions),
            key=lambda p: (p["presentedAt"], p["id"]),
            reverse=True,
        )
        summary = {
            "id": int(dep),
            "name": profile["deputado_nome"].strip(),
            "party": profile["deputado_siglaPartido"],
            "uf": profile["deputado_siglaUf"],
            "photoUrl": profile["deputado_urlFoto"],
            "inExercise": dep in in_exercise,
            "sourceUrl": DEPUTY_URL.format(id=dep),
            "participation": indicator(recorded, len(eligible)),
            "governmentAlignment": indicator(gov_count, gov_total),
            "partyAlignment": indicator(party_count, party_total),
            "authoredCount": len(authored),
            "firstSignerCount": sum(1 for p in authored if p["firstSigner"]),
            "requirementsCount": sum(1 for p in data.authorship[dep] if p in data.requirements),
            "candidacy2026": candidacies.get(dep),
        }
        deputies.append(summary)
        deputy_docs[dep] = {**summary, "exercisePeriods": periods, "authored": authored, "votes": votes}

    deputies.sort(key=lambda d: (sort_name(d["name"]), d["id"]))
    return {
        "deputies": deputies,
        "deputy_docs": deputy_docs,
        "roll_calls": roll_calls,
        "roll_call_docs": roll_call_docs,
        "propositions": len(data.propositions),
    }


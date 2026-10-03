"""Contract v3 for the Câmara: members with one mandate per legislature, classified roll calls, propositions.

Every indicator is `{"all": {count, total}, "merit": {count, total}}` over plenary roll calls of the
mandate's legislature; nothing here computes a percentage (AD-004). The v2 path (`compute`) is not
used, so v2 output cannot drift.
"""

import re
from collections import Counter, defaultdict
from collections.abc import Callable, Iterable
from dataclasses import dataclass, field
from datetime import date, timedelta

from mandato_etl import classify, readers
from mandato_etl.compute import (
    AUTHORED_TYPES, DEPUTY_URL, PLENARY, PROPOSITION_URL, REQUIREMENT_TYPES, ROLL_CALL_URL, in_periods, seconds,
    sort_name,
)

HOUSE = "camara"
SCHEMA_VERSION = 3
FIRST_DAY = "2023-02-01"
# Fixed by the Constitution; checked against recorded `/legislaturas/<id>` responses in the tests.
LEGISLATURES = {57: ("2023-02-01", "2027-01-31"), 58: ("2027-02-01", "2031-01-31")}
LEGISLATURE_URL = "https://dadosabertos.camara.leg.br/api/v2/legislaturas/{id}"
# Proposition types whose inteiro teor is extracted when a plenary roll call with records decided on them.
TEXT_TYPES = {"PL", "PLP", "PEC", "MPV", "PDL"}
VOTED = {"yes", "no", "abstention", "obstruction"}
BASES = {"all": lambda rc: True, "merit": lambda rc: rc["kind"] in classify.MERIT}


class ContractError(Exception):
    """The sources cannot be written into contract v3; the message names the record."""


def legislature_of(day: str) -> int:
    for legislature, (start, end) in LEGISLATURES.items():
        if start <= day <= end:
            return legislature
    raise ContractError(f"{day} is outside every known legislature")


def started(local_day: str) -> list[int]:
    return [n for n, (start, _) in sorted(LEGISLATURES.items()) if start <= local_day]


def next_start(legislature: int) -> str:
    return (date.fromisoformat(LEGISLATURES[legislature][1]) + timedelta(days=1)).isoformat() + "T00:00:00"


def exercise_periods(history: Iterable[dict], legislature: int, end: str) -> list[dict]:
    """Half-open `[start, end)` intervals in `Exercício`, from the entries dated inside `legislature` only."""
    first, last = LEGISLATURES[legislature]
    entries = sorted((h for h in history if h["situacao"] and first <= h["dataHora"][:10] <= last),
                     key=lambda h: h["dataHora"])
    periods, start = [], None
    for h in entries:
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


def party_majority(positions: list[str], own: str) -> str | None:
    """Most frequent voted position of the other party members (`positions` includes `own` once)."""
    counts = Counter(p for p in positions if p in VOTED)
    if own in counts:
        counts[own] -= 1
    top = [(p, n) for p, n in counts.most_common() if n > 0][:2]
    if not top or (len(top) == 2 and top[0][1] == top[1][1]):
        return None
    return top[0][0]


def basis(count: int, total: int) -> dict:
    return {"count": count, "total": total}


def _text(value: str) -> str | None:
    return value.strip() or None


def _int(value: str) -> int | None:
    return int(value) if value.strip() else None


@dataclass
class Loaded:
    roll_calls: dict[str, dict] = field(default_factory=dict)
    # rollCallId -> {deputyId: vote row}; the latest record wins
    votes: dict[str, dict[str, dict]] = field(default_factory=lambda: defaultdict(dict))
    orientations: dict[str, list[tuple[str, str]]] = field(default_factory=lambda: defaultdict(list))
    links: dict[str, dict] = field(default_factory=dict)
    # deputyId -> {propositionId: firstSigner}
    authorship: dict[str, dict[str, bool]] = field(default_factory=lambda: defaultdict(dict))
    propositions: dict[str, dict] = field(default_factory=dict)


def load(read: Callable[[str], Iterable[dict]]) -> Loaded:
    """Reads every Câmara source through `read(kind)`, from `2023-02-01`, each roll call tagged with its legislature."""
    data = Loaded()
    for r in read("votacoes"):
        if r["data"] < FIRST_DAY:
            continue
        try:
            legislature = legislature_of(r["data"])
        except ContractError:
            raise ContractError(f"roll call {r['id']} dated {r['data']} is outside every known legislature") from None
        data.roll_calls[r["id"]] = {**r, "legislature": legislature, "at": seconds(r["dataHoraRegistro"])}

    for r in read("votacoesProposicoes"):
        if r["idVotacao"] in data.roll_calls and r["idVotacao"] not in data.links and r["proposicao_id"]:
            data.links[r["idVotacao"]] = r

    for r in read("votacoesOrientacoes"):
        if r["idVotacao"] in data.roll_calls and r["orientacao"]:
            data.orientations[r["idVotacao"]].append((r["siglaBancada"].strip(), r["orientacao"]))

    for r in read("votacoesVotos"):
        if r["idVotacao"] not in data.roll_calls:
            continue
        previous = data.votes[r["idVotacao"]].get(r["deputado_id"])
        if previous is None or r["dataHoraVoto"] >= previous["dataHoraVoto"]:
            data.votes[r["idVotacao"]][r["deputado_id"]] = r

    for r in read("proposicoesAutores"):
        if r["idDeputadoAutor"] and r["proponente"] == "1":
            authored = data.authorship[r["idDeputadoAutor"]]
            authored[r["idProposicao"]] = authored.get(r["idProposicao"], False) or r["ordemAssinatura"] == "1"

    wanted = {p for props in data.authorship.values() for p in props} | {r["proposicao_id"] for r in data.links.values()}
    for r in read("proposicoes"):
        if r["id"] in wanted:
            data.propositions[r["id"]] = r
    return data


def member_ids(data: Loaded, lists: dict[int, list[dict]]) -> dict[str, set[int]]:
    """deputyId -> legislatures: listed by `/deputados?idLegislatura`, or holding a record of that legislature."""
    mandates = defaultdict(set)
    for legislature, deputies in lists.items():
        for d in deputies:
            mandates[str(d["id"])].add(legislature)
    for votes in data.votes.values():
        for dep, row in votes.items():
            legislature = int(row["deputado_idLegislatura"])
            if legislature in lists:
                mandates[dep].add(legislature)
    return mandates


def _roll_call(rc: dict, votes: dict[str, dict], data: Loaded, rules: list[dict]) -> dict | None:
    """The full roll-call document, or `None` for a committee roll call without individual records."""
    ballot = classify.ballot_of([v["voto"] for v in votes.values()], rc["ultimaAberturaVotacao_descricao"])
    if rc["siglaOrgao"] != PLENARY and ballot == "symbolic":
        return None
    kind, rule = classify.classify(rc, rules)
    try:
        orientations = [
            {"bench": bench, "official": official, "position": classify.orientation_of(official)}
            for bench, official in data.orientations.get(rc["id"], [])
        ]
        positions = {dep: classify.position_of(v["voto"], ballot) for dep, v in votes.items()}
    except classify.UnknownValueError as e:
        raise ContractError(f"roll call {rc['id']}: unknown value {e.args[0]!r}") from None
    government = next((o["position"] for o in orientations if o["bench"].casefold() == "governo"), None)

    by_party = defaultdict(list)
    for dep, v in votes.items():
        by_party[v["deputado_siglaPartido"]].append(positions[dep])
    if ballot == "symbolic":
        tallies = None
    elif ballot == "secret":
        official = (rc["votosSim"], rc["votosNao"], rc["votosOutros"])
        tallies = None if not votes or not all(official) else dict(zip(("yes", "no", "others"), map(int, official)))
    else:
        values = [v["voto"] for v in votes.values()]
        yes, no = values.count("Sim"), values.count("Não")
        tallies = {"yes": yes, "no": no, "others": sum(1 for v in values if v) - yes - no}
    link = data.links.get(rc["id"])
    return {
        "house": HOUSE,
        "id": rc["id"],
        "legislature": rc["legislature"],
        "date": rc["data"],
        "organ": rc["siglaOrgao"],
        "description": rc["descricao"].strip(),
        "approved": {"1": True, "0": False}.get(rc["aprovacao"]),
        "ballot": ballot,
        "kind": kind,
        "kindRule": rule,
        "propositionId": int(link["proposicao_id"]) if link else None,
        "tallies": tallies,
        "governmentOrientation": government,
        "sourceUrl": ROLL_CALL_URL.format(id=rc["id"]),
        "openingDescription": _text(rc["ultimaAberturaVotacao_descricao"]),
        "lastPresentationDescription": _text(rc["ultimaApresentacaoProposicao_descricao"]),
        "orientations": sorted(orientations, key=lambda o: (o["bench"].casefold(), o["bench"], o["official"])),
        "votes": [
            {
                "memberId": int(dep),
                "official": classify.published_official(HOUSE, v["voto"]),
                "position": positions[dep],
                "party": v["deputado_siglaPartido"],
                "partyMajority": party_majority(by_party[v["deputado_siglaPartido"]], positions[dep]),
            }
            for dep, v in sorted(votes.items(), key=lambda item: int(item[0]))
        ],
    }


def _proposition(prop: str, data: Loaded, link: dict | None, authors: list[dict]) -> dict:
    row = data.propositions.get(prop)
    if row is not None:
        fields = {
            "type": row["siglaTipo"], "number": _int(row["numero"]), "year": _int(row["ano"]),
            "summary": _text(row["ementa"]), "presentedAt": row["dataApresentacao"][:10],
            "status": _text(row["ultimoStatus_descricaoSituacao"]),
        }
    else:
        fields = {
            "type": link["proposicao_siglaTipo"], "number": _int(link["proposicao_numero"]),
            "year": _int(link["proposicao_ano"]), "summary": _text(link["proposicao_ementa"]),
            "presentedAt": None, "status": None,
        }
    return {"house": HOUSE, "id": int(prop), **fields, "sourceUrl": PROPOSITION_URL.format(id=prop), "authors": authors}


def presented_in(row: dict | None) -> int | None:
    """The legislature a proposition was presented in, or `None` before the first known legislature."""
    if row is None or row["dataApresentacao"][:10] < FIRST_DAY:
        return None
    return legislature_of(row["dataApresentacao"][:10])


def assemble(
    data: Loaded,
    lists: dict[int, list[dict]],
    histories: dict[str, list[dict]],
    ruleset: dict,
    now_local: str,
) -> dict:
    """Every v3 document but the full texts, plus the full-text targets and the coverage rows."""
    rules = ruleset["rules"]
    docs = {}
    for rc_id, rc in data.roll_calls.items():
        doc = _roll_call(rc, data.votes.get(rc_id, {}), data, rules)
        if doc is not None:
            docs[rc_id] = doc

    mandates = member_ids(data, lists)
    listed = {(str(d["id"]), n): d for n, deputies in lists.items() for d in deputies}
    latest: dict[str, dict] = {}
    latest_in: dict[tuple[str, int], dict] = {}
    for rc_id, votes in data.votes.items():
        if rc_id not in docs:
            continue
        legislature = data.roll_calls[rc_id]["legislature"]
        for dep, v in votes.items():
            for store, key in ((latest, dep), (latest_in, (dep, legislature))):
                if key not in store or v["dataHoraVoto"] >= store[key]["dataHoraVoto"]:
                    store[key] = v

    members = []
    for dep, legislatures in mandates.items():
        own_mandates = []
        for legislature in sorted(legislatures):
            source = latest_in.get((dep, legislature))
            entry = listed.get((dep, legislature))
            if source is None and entry is None:
                # a record whose `deputado_idLegislatura` differs from its roll call's date
                source = latest[dep]
            party = source["deputado_siglaPartido"] if source else entry["siglaPartido"]
            uf = source["deputado_siglaUf"] if source else entry["siglaUf"]
            periods = exercise_periods(histories.get(dep, []), legislature, min(now_local, next_start(legislature)))
            own_mandates.append({
                "legislature": legislature, "party": party, "uf": uf, "exercisePeriods": periods,
                **_proposition_counts(dep, legislature, data),
            })
        source = latest.get(dep)
        if source:
            name, party, uf, photo = (source["deputado_nome"], source["deputado_siglaPartido"],
                                      source["deputado_siglaUf"], source["deputado_urlFoto"])
        else:
            entry = listed[(dep, max(legislatures))]
            name, party, uf, photo = entry["nome"], entry["siglaPartido"], entry["siglaUf"], entry["urlFoto"]
        members.append({
            "house": HOUSE, "id": int(dep), "name": name.strip(), "party": party, "uf": uf, "photoUrl": photo,
            "sourceUrl": DEPUTY_URL.format(id=dep), "mandates": own_mandates,
        })

    authors = defaultdict(list)
    for dep, props in data.authorship.items():
        if dep in mandates:
            for prop, first in props.items():
                authors[prop].append({"memberId": int(dep), "firstSigner": first})
    linked = {data.links[rc_id]["proposicao_id"]: data.links[rc_id] for rc_id in sorted(docs) if rc_id in data.links}
    authored = {
        prop for prop in authors
        if presented_in(data.propositions.get(prop)) and data.propositions[prop]["siglaTipo"] in AUTHORED_TYPES
    }
    propositions = [
        _proposition(prop, data, linked.get(prop), sorted(authors.get(prop, []), key=lambda a: a["memberId"]))
        for prop in authored | set(linked)
    ]

    types = {p["id"]: p["type"] for p in propositions}
    targets = sorted({
        doc["propositionId"] for doc in docs.values()
        if doc["organ"] == PLENARY and doc["propositionId"] is not None and doc["votes"]
        and types[doc["propositionId"]] in TEXT_TYPES
    })
    at = {rc_id: data.roll_calls[rc_id]["at"] for rc_id in docs}
    result = finish(HOUSE, docs, at, members, propositions, ruleset, sorted(lists))
    return {**result, "targets": targets}


def finish(house: str, docs: dict, at: dict, members: list, propositions: list, ruleset: dict,
           legislatures: list[int], symbolic: bool = True) -> dict:
    """The house-independent half of the contract: indicators per mandate, ordering, coverage and files.

    `docs` maps a roll-call id to its full document and `at` to its timestamp; each member's
    mandates already hold their periods and proposition counts. `symbolic=False` is a house that
    publishes no symbolic roll-call records, whose symbolic counts are `null` (door 9).
    """
    by_member = defaultdict(dict)
    for rc_id, doc in docs.items():
        for v in doc["votes"]:
            by_member[str(v["memberId"])][rc_id] = v
    plenary = [doc for doc in docs.values() if doc["organ"] == PLENARY]
    for member in members:
        dep = str(member["id"])
        for mandate in member["mandates"]:
            mandate.update(_indicators(dep, mandate["legislature"], mandate["exercisePeriods"], plenary, at,
                                       by_member[dep]))
            if not symbolic:
                mandate["symbolicMerit"] = None
    members.sort(key=lambda m: (sort_name(m["name"]), m["id"]))
    propositions.sort(key=lambda p: p["id"])
    propositions.sort(key=lambda p: p["presentedAt"] or "", reverse=True)

    ordered = sorted(docs.values(), key=lambda d: d["id"])
    ordered.sort(key=lambda d: d["date"], reverse=True)
    summary_keys = ("openingDescription", "lastPresentationDescription", "orientations", "votes")
    roll_calls = [{k: v for k, v in d.items() if k not in summary_keys} for d in ordered]

    coverage = []
    for legislature in legislatures:
        own = [d for d in roll_calls if d["legislature"] == legislature]
        ballots = Counter(d["ballot"] for d in own)
        coverage.append({
            "legislature": legislature,
            "through": max((d["date"] for d in own), default=None),
            "rollCalls": {"nominal": ballots["nominal"], "secret": ballots["secret"],
                          "symbolic": ballots["symbolic"] if symbolic else None},
            "unclassified": sum(1 for d in own if d["kind"] == "unclassified"),
            "members": sum(1 for m in members if any(x["legislature"] == legislature for x in m["mandates"])),
        })

    files = {
        "members.json": members,
        "roll-calls.json": roll_calls,
        "propositions.json": propositions,
        "classification-rules.json": [{**rule, "house": house} for rule in ruleset["rules"]],
    }
    files |= {f"roll-calls/{rc_id}.json": doc for rc_id, doc in docs.items() if doc["ballot"] != "symbolic"}
    return {"files": files, "coverage": coverage}


def _indicators(dep, legislature, periods, plenary, at, own_votes) -> dict:
    mine = [d for d in plenary if d["legislature"] == legislature]
    result = {"participation": {}, "governmentAlignment": {}, "partyAlignment": {}}
    for name, keep in BASES.items():
        eligible = [d for d in mine if keep(d) and d["ballot"] != "symbolic" and in_periods(at[d["id"]], periods)]
        recorded = sum(1 for d in eligible if d["id"] in own_votes and own_votes[d["id"]]["position"] != "notVoting")
        result["participation"][name] = basis(recorded, len(eligible))

        votes = [(d, own_votes[d["id"]]) for d in mine if keep(d) and d["id"] in own_votes]
        government = [(v["position"], d["governmentOrientation"]) for d, v in votes
                      if v["position"] in VOTED and d["governmentOrientation"] in VOTED]
        result["governmentAlignment"][name] = basis(sum(a == b for a, b in government), len(government))
        party = [(v["position"], v["partyMajority"]) for d, v in votes
                 if v["position"] in VOTED and v["partyMajority"] is not None]
        result["partyAlignment"][name] = basis(sum(a == b for a, b in party), len(party))
    result["symbolicMerit"] = sum(
        1 for d in mine
        if d["ballot"] == "symbolic" and d["kind"] in classify.MERIT and in_periods(at[d["id"]], periods)
    )
    return result


def _proposition_counts(dep: str, legislature: int, data: Loaded) -> dict:
    own = [
        (data.propositions[p]["siglaTipo"], first) for p, first in data.authorship.get(dep, {}).items()
        if presented_in(data.propositions.get(p)) == legislature
    ]
    return {
        "authoredCount": sum(1 for kind, _ in own if kind in AUTHORED_TYPES),
        "firstSignerCount": sum(1 for kind, first in own if kind in AUTHORED_TYPES and first),
        "requirementsCount": sum(1 for kind, _ in own if kind in REQUIREMENT_TYPES),
    }


# --- Senate (etl-senado). The Senate's records are mapped onto the same documents and handed to
# `finish`; only what the Senate publishes differently lives here.

SENATE = "senado"
SENATE_MEMBER_URL = "https://www25.senado.leg.br/web/senadores/senador/-/perfil/{id}"
SENATE_PHOTO_URL = "https://www.senado.leg.br/senadores/img/fotos-oficiais/senador{id}.jpg"
SENATE_ROLL_CALL_URL = "https://legis.senado.leg.br/dadosabertos/votacao?codigoSessao={session}"
SENATE_PROPOSITION_URL = "https://www25.senado.leg.br/web/atividade/materias/-/materia/{materia}"
# A process with no MATE code has no public page; its API record is the source.
SENATE_PROCESS_URL = "https://legis.senado.leg.br/dadosabertos/processo/{id}"
SENATE_LEGISLATURE_URL = "https://legis.senado.leg.br/dadosabertos/plenario/legislatura/{start}"
SENATE_AUTHORED = re.compile(r"^(PL|PLP|PEC|PDL|PRS) (\d+)/(\d{4})$")
SENATE_REQUIREMENTS = ("RQS ", "REQ ", "INS ")
NO_PARTY = "S/Partido"


def _number(value) -> int | None:
    return int(value) if value not in (None, "") and str(value).strip().isdigit() else None


def senate_roll_calls(records: list[dict], orientations: dict, rules: list[dict]) -> tuple[dict, dict]:
    """`({id: roll-call document}, {id: at})` for deduplicated `/votacao` records of built legislatures.

    `orientations` maps a `sequencialVotacao` to its non-null `(partido, voto)` pairs.
    """
    docs, at = {}, {}
    for r in records:
        rc_id = str(r["codigoSessaoVotacao"])
        ballot = {"S": "secret", "N": "nominal"}.get(r["votacaoSecreta"])
        if ballot is None:
            raise ContractError(f"roll call {rc_id}: unknown votacaoSecreta {r['votacaoSecreta']!r}")
        kind, rule = classify.classify(r, rules)
        votes = {str(v["codigoParlamentar"]): v for v in r["votos"]}
        try:
            positions = {m: classify.senate_position_of(v["siglaVotoParlamentar"]) for m, v in votes.items()}
            own = orientations.get(r["sequencialVotacao"], []) if r["sequencialVotacao"] is not None else []
            oriented = [{"bench": bench, "official": official, "position": classify.senate_orientation_of(official)}
                        for bench, official in own]
        except classify.UnknownValueError as e:
            raise ContractError(f"roll call {rc_id}: unknown value {e.args[0]!r}") from None
        oriented.sort(key=lambda o: (o["bench"].casefold(), o["bench"], o["official"]))
        government = next((o["position"] for o in oriented if o["bench"].casefold() == "governo"), None)
        by_party = defaultdict(list)
        for m, v in votes.items():
            by_party[v["siglaPartidoParlamentar"]].append(positions[m])
        if ballot == "secret":
            official = (r["totalVotosSim"], r["totalVotosNao"], r["totalVotosAbstencao"])
            tallies = None if None in official else dict(zip(("yes", "no", "others"), map(int, official)))
        else:
            values = Counter(v["siglaVotoParlamentar"] for v in votes.values())
            tallies = {"yes": values["Sim"], "no": values["Não"], "others": values["Abstenção"]}
        description = (r["descricaoVotacao"] or "").strip()
        docs[rc_id] = {
            "house": SENATE,
            "id": rc_id,
            "legislature": legislature_of(r["dataSessao"]),
            "date": r["dataSessao"],
            "organ": PLENARY,
            "description": description,
            "approved": {"A": True, "R": False}.get(r["resultadoVotacao"]),
            "ballot": ballot,
            "kind": kind,
            "kindRule": rule,
            "propositionId": r["idProcesso"],
            "tallies": tallies,
            "governmentOrientation": government,
            "sourceUrl": SENATE_ROLL_CALL_URL.format(session=r["codigoSessao"]),
            "openingDescription": description or None,
            "lastPresentationDescription": None,
            "orientations": oriented,
            "votes": [
                {
                    "memberId": int(m),
                    "official": classify.published_official(SENATE, v["siglaVotoParlamentar"]),
                    "position": positions[m],
                    "party": v["siglaPartidoParlamentar"],
                    "partyMajority": None if v["siglaPartidoParlamentar"] == NO_PARTY
                    else party_majority(by_party[v["siglaPartidoParlamentar"]], positions[m]),
                }
                for m, v in sorted(votes.items(), key=lambda item: int(item[0]))
            ],
        }
        at[rc_id] = r["dataSessao"] + "T00:00:00"
    return docs, at


def _exercises(entry: dict) -> list[dict]:
    return [e for m in readers.as_list(entry.get("Mandatos", {}).get("Mandato"))
            for e in readers.as_list((m.get("Exercicios") or {}).get("Exercicio"))]


def senate_periods(entry: dict, legislature: int, now_local: str) -> list[dict]:
    """Each `Exercicio` as `[DataInicio, day after DataFim)`, clipped to `legislature`; an open one ends at the
    earlier of the build time and the next legislature's start (AC 9)."""
    first, last = LEGISLATURES[legislature]
    bound = next_start(legislature)
    periods = []
    for e in _exercises(entry):
        start, end = e.get("DataInicio"), e.get("DataFim")
        if not start or start > last or (end and end < first):
            continue
        period_end = ((date.fromisoformat(end) + timedelta(days=1)).isoformat() + "T00:00:00") if end else now_local
        period = {"start": max(start, first) + "T00:00:00", "end": min(period_end, bound)}
        if period["start"] < period["end"]:
            periods.append(period)
    return sorted(periods, key=lambda p: (p["start"], p["end"]))


def _intersects(entry: dict, legislature: int) -> bool:
    first, last = LEGISLATURES[legislature]
    return any(e.get("DataInicio") and e["DataInicio"] <= last and (not e.get("DataFim") or e["DataFim"] >= first)
               for e in _exercises(entry))


def _code(entry: dict) -> str:
    return str(entry["IdentificacaoParlamentar"]["CodigoParlamentar"])


def senate_mandates(lists: dict[int, list[dict]], records: list[dict]) -> dict[str, list[int]]:
    """memberId -> legislatures: listed for it and with an `Exercicio` intersecting it or a vote record in it (AC 8)."""
    voted = {(str(v["codigoParlamentar"]), legislature_of(r["dataSessao"])) for r in records for v in r["votos"]}
    mandates = defaultdict(set)
    for legislature, entries in lists.items():
        for entry in entries:
            code = _code(entry)
            if _intersects(entry, legislature) or (code, legislature) in voted:
                mandates[code].add(legislature)
    return {code: sorted(found) for code, found in mandates.items()}


def _authored(process: dict) -> re.Match | None:
    if not (process.get("autoria") or "").startswith("Senador"):
        return None
    return SENATE_AUTHORED.match(process.get("identificacao") or "")


def _single_author(process: dict) -> bool:
    autoria = process["autoria"]
    return "," not in autoria and " e outros" not in autoria


def _inside(process: dict, legislature: int) -> bool:
    first, last = LEGISLATURES[legislature]
    return first <= (process.get("dataApresentacao") or "")[:10] <= last


def senate_multi_author(processes: dict[str, list[dict]], mandates: dict[str, list[int]]) -> list[int]:
    """Ids of the authored processes that name more than one author; their first signer needs `/processo/<id>`."""
    return sorted({
        p["id"] for member, own in processes.items() for p in own
        if _authored(p) and not _single_author(p) and any(_inside(p, n) for n in mandates.get(member, []))
    })


def assemble_senado(
    records: list[dict],
    orientations: dict,
    lists: dict[int, list[dict]],
    processes: dict[str, list[dict]],
    details: dict[int, dict],
    ruleset: dict,
    now_local: str,
) -> dict:
    """Every Senate v3 document, the coverage rows and, per legislature, the vote records that disagree with
    the exercise periods (AC 23)."""
    docs, at = senate_roll_calls(records, orientations, ruleset["rules"])
    mandates = senate_mandates(lists, records)
    listed = {(_code(e), n): e for n, entries in lists.items() for e in entries}
    latest: dict[str, tuple] = {}
    latest_in: dict[tuple[str, int], tuple] = {}
    for r in records:
        rc_id = str(r["codigoSessaoVotacao"])
        doc = docs[rc_id]
        for record in r["votos"]:
            key = (doc["date"], int(rc_id))
            member = str(record["codigoParlamentar"])
            for store, k in ((latest, member), (latest_in, (member, doc["legislature"]))):
                if k not in store or key >= store[k][0]:
                    store[k] = (key, record)

    first_signer = {}
    authors = defaultdict(list)
    authored_rows = {}
    members = []
    for code, legislatures in mandates.items():
        own_mandates = []
        for legislature in legislatures:
            entry = listed[(code, legislature)]
            source = latest_in.get((code, legislature))
            if source:
                party, uf = source[1]["siglaPartidoParlamentar"], source[1]["siglaUFParlamentar"]
            else:
                party, uf = _list_party(entry), _mandate_uf(entry, legislature)
            counts = {"authoredCount": 0, "firstSignerCount": 0, "requirementsCount": 0}
            for p in processes.get(code, []):
                if not _inside(p, legislature):
                    continue
                if (p.get("identificacao") or "").startswith(SENATE_REQUIREMENTS):
                    counts["requirementsCount"] += 1
                if _authored(p):
                    first = _single_author(p) or details.get(p["id"], {}).get(1) == code
                    first_signer[(p["id"], code)] = first
                    counts["authoredCount"] += 1
                    counts["firstSignerCount"] += first
                    authored_rows[p["id"]] = p
            own_mandates.append({"legislature": legislature, "party": party, "uf": uf,
                                 "exercisePeriods": senate_periods(entry, legislature, now_local), **counts})
        source = latest.get(code)
        entry = listed[(code, legislatures[-1])]
        if source:
            vote = source[1]
            name, party, uf = vote["nomeParlamentar"], vote["siglaPartidoParlamentar"], vote["siglaUFParlamentar"]
        else:
            name, party, uf = (entry["IdentificacaoParlamentar"]["NomeParlamentar"], _list_party(entry),
                               own_mandates[-1]["uf"])
        members.append({
            "house": SENATE, "id": int(code), "name": name.strip(), "party": party, "uf": uf,
            "photoUrl": SENATE_PHOTO_URL.format(id=code), "sourceUrl": SENATE_MEMBER_URL.format(id=code),
            "mandates": own_mandates,
        })
    for (prop, code), first in sorted(first_signer.items(), key=lambda item: (item[0][0], int(item[0][1]))):
        authors[prop].append({"memberId": int(code), "firstSigner": first})

    propositions = {}
    for r in records:
        if r["idProcesso"] is not None:
            propositions[r["idProcesso"]] = {
                "house": SENATE, "id": r["idProcesso"], "type": r["sigla"], "number": _number(r["numero"]),
                "year": _number(r["ano"]), "summary": (r["ementa"] or "").strip() or None, "presentedAt": None,
                "status": None, "sourceUrl": _proposition_url(r["idProcesso"], r["codigoMateria"]), "authors": [],
            }
    for prop, p in authored_rows.items():
        match = _authored(p)
        propositions[prop] = {
            "house": SENATE, "id": prop, "type": match.group(1), "number": int(match.group(2)),
            "year": int(match.group(3)), "summary": (p.get("ementa") or "").strip() or None,
            "presentedAt": p["dataApresentacao"][:10], "status": (p.get("situacaoAtual") or "").strip() or None,
            "sourceUrl": _proposition_url(prop, p.get("codigoMateria")), "authors": authors[prop],
        }

    mismatches = Counter()
    periods = {(str(m["id"]), x["legislature"]): x["exercisePeriods"] for m in members for x in m["mandates"]}
    for rc_id, doc in docs.items():
        voters = {str(v["memberId"]) for v in doc["votes"]}
        for member in voters:
            if not in_periods(at[rc_id], periods.get((member, doc["legislature"]), [])):
                mismatches[doc["legislature"]] += 1
        for (member, legislature), own in periods.items():
            if legislature == doc["legislature"] and member not in voters and in_periods(at[rc_id], own):
                mismatches[legislature] += 1

    result = finish(SENATE, docs, at, members, list(propositions.values()), ruleset, sorted(lists), symbolic=False)
    return {**result, "mismatches": dict(mismatches)}


def _list_party(entry: dict) -> str:
    return entry["IdentificacaoParlamentar"].get("SiglaPartidoParlamentar") or NO_PARTY


def _mandate_uf(entry: dict, legislature: int) -> str:
    own = readers.as_list(entry.get("Mandatos", {}).get("Mandato"))
    for m in own:
        numbers = {str((m.get(k) or {}).get("NumeroLegislatura")) for k in
                   ("PrimeiraLegislaturaDoMandato", "SegundaLegislaturaDoMandato")}
        if str(legislature) in numbers and m.get("UfParlamentar"):
            return m["UfParlamentar"]
    return entry["IdentificacaoParlamentar"].get("UfParlamentar") or next(
        (m["UfParlamentar"] for m in own if m.get("UfParlamentar")), "")


def _proposition_url(prop: int, materia) -> str:
    return SENATE_PROPOSITION_URL.format(materia=materia) if materia else SENATE_PROCESS_URL.format(id=prop)

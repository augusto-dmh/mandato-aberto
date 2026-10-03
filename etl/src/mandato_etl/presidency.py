"""Contract v4, presidency scope: acts, their statuses, terms, stages, joint roll calls and member counts.

Every status comes from the published rule table `STATUS_RULES` by exact match on the verbatim
official value (door 4); a value outside it stops the build. Joint votes resolve to house members by
exact normalised name, UF and exercise period, after the alias file, and an unresolved vote stops the
build (door 6, AC 31).
"""

import json
import re
import unicodedata
from collections import defaultdict
from datetime import date, timedelta
from pathlib import Path

from mandato_etl import contract_v3
from mandato_etl.readers import as_list

ALIASES = Path(__file__).resolve().parents[2] / "inputs" / "joint-vote-aliases.json"
HOUSES = ("camara", "senado")
STATUS_RULES_VERSION = 1
TERMS = (
    {"id": "2023-2026", "start": "2023-01-01", "end": "2027-01-04", "holder": "Luiz Inácio Lula da Silva",
     "sourceUrl": "https://legis.senado.leg.br/dadosabertos/plenario/resultado/cn/20230101"},
    # EC 111/2021: the term elected in 2026 starts on 5 January. The holder is set by a sourced commit
    # after the inauguration (plan, resolved open question 2).
    {"id": "2027-2030", "start": "2027-01-05", "end": "2031-01-04", "holder": None, "sourceUrl": None},
)
MP_URL = "https://www.congressonacional.leg.br/materias/medidas-provisorias/-/mpv/{materia}"
VETO_URL = "https://www.congressonacional.leg.br/materias/vetos/-/veto/detalhe/{codigo}"
BILL_URL = "https://www.camara.leg.br/propostas-legislativas/{id}"
DEVICE_URL = "https://legis.senado.leg.br/dadosabertos/plenario/resultado/veto/dispositivo/{codigo}"
BILL_TYPES = ("PL", "PLP", "PEC")
EXECUTIVE = ("Poder Executivo", "30000")
MP_STATUSES = ("pending", "approved", "approvedAmended", "rejected", "lapsed", "revoked", "returned")
VETO_STATUSES = ("pending", "decided")
BILL_STATUSES = ("inProgress", "law", "vetoedTotally", "withdrawn", "archived")
DEVICE_STATUSES = ("kept", "overridden", "prejudged", "pending")
POSITIONS = {"Sim": "yes", "Não": "no", "Abstenção": "abstention", "Obstrução": "obstruction", "Branco": "blank",
             "Art. 17": "presiding"}
METHODS = {"Cédula": "cedula", "Painel": "painel"}
RESULTS = {"kept": "kept", "overridden": "overridden"}
CAMARA_LAW = "Transformado em Norma Jurídica"


def _rule(id, kind, source, field, value, status, description):
    return {"id": id, "kind": kind, "source": source, "field": field, "officialValue": value, "status": status,
            "description": description}


_MP, _DEVICE, _VETO, _BILL = "provisionalMeasure", "device", "veto", "bill"
_CN = "Congresso Nacional"
STATUS_RULES = [
    _rule("mpv.01", _MP, "congresso", "siglaTipoDeliberacao", "APROVADO_NA_INTEGRA", "approved",
          f"Deliberação APROVADO_NA_INTEGRA no {_CN}: a medida provisória foi aprovada sem alteração e "
          "convertida em lei."),
    _rule("mpv.02", _MP, "congresso", "siglaTipoDeliberacao", "APROVADO_PLV", "approvedAmended",
          f"Deliberação APROVADO_PLV no {_CN}: a medida provisória foi aprovada com alterações, na forma de um "
          "projeto de lei de conversão."),
    _rule("mpv.03", _MP, "congresso", "siglaTipoDeliberacao", "PERDA_EFICACIA", "lapsed",
          f"Deliberação PERDA_EFICACIA no {_CN}: a medida provisória perdeu a eficácia porque o prazo para sua "
          "votação terminou."),
    _rule("mpv.04", _MP, "congresso", "siglaTipoDeliberacao", "REVOGADO", "revoked",
          f"Deliberação REVOGADO no {_CN}: a medida provisória foi revogada antes de ser deliberada."),
    _rule("mpv.05", _MP, "congresso", "siglaTipoDeliberacao", "REJEITADO_PLENARIO", "rejected",
          f"Deliberação REJEITADO_PLENARIO no {_CN}: a medida provisória foi rejeitada em Plenário."),
    _rule("mpv.06", _MP, "congresso", "siglaTipoDeliberacao", "REJEITADO_PLENARIO_CD", "rejected",
          f"Deliberação REJEITADO_PLENARIO_CD no {_CN}: a medida provisória foi rejeitada no Plenário da Câmara "
          "dos Deputados."),
    _rule("mpv.07", _MP, "congresso", "siglaTipoDeliberacao", "INADIMITIDA_URGENCIA", "rejected",
          f"Deliberação INADIMITIDA_URGENCIA no {_CN}: a medida provisória não foi admitida por não atender aos "
          "pressupostos constitucionais de urgência."),
    _rule("mpv.08", _MP, "congresso", "siglaTipoDeliberacao", "IMPUGNADO_PRESIDENCIA", "returned",
          f"Deliberação IMPUGNADO_PRESIDENCIA no {_CN}: a medida provisória foi devolvida pela Presidência do "
          f"{_CN}."),
    _rule("mpv.09", _MP, "congresso", "siglaTipoDeliberacao", None, "pending",
          f"Sem deliberação e com tramitando igual a Sim no {_CN}: a medida provisória ainda está em "
          "tramitação."),
    _rule("device.01", _DEVICE, "congresso", "Situacao", "Mantido", "kept",
          f"Situação Mantido no resultado do veto: o {_CN} manteve o veto a este dispositivo."),
    _rule("device.02", _DEVICE, "congresso", "Situacao", "Rejeitado", "overridden",
          f"Situação Rejeitado no resultado do veto: o {_CN} rejeitou o veto, e o dispositivo passa a valer "
          "como lei."),
    _rule("device.03", _DEVICE, "congresso", "Situacao", "Prejudicado", "prejudged",
          "Situação Prejudicado no resultado do veto: a deliberação sobre este dispositivo ficou prejudicada."),
    _rule("device.04", _DEVICE, "congresso", "Situacao", "Não Apreciado", "pending",
          "Situação Não Apreciado no resultado do veto: o dispositivo ainda não foi deliberado em sessão "
          "conjunta."),
    _rule("veto.01", _VETO, "mandato-aberto", "Dispositivos.Situacao", None, "pending",
          "Ao menos um dispositivo do veto tem a situação Não Apreciado: o veto ainda aguarda deliberação."),
    _rule("veto.02", _VETO, "mandato-aberto", "Dispositivos.Situacao", None, "decided",
          f"Nenhum dispositivo do veto tem a situação Não Apreciado: o {_CN} deliberou sobre todo o veto."),
    _rule("bill.01", _BILL, "congresso", "normaGerada", None, "law",
          "O processo do projeto no Senado Federal informa uma normaGerada: o projeto foi transformado em lei."),
    _rule("bill.02", _BILL, "camara", "ultimoStatus_descricaoSituacao", CAMARA_LAW, "law",
          f"Situação {CAMARA_LAW} na Câmara dos Deputados: o projeto foi transformado em lei."),
    _rule("bill.03", _BILL, "congresso", "MateriaVetada", None, "vetoedTotally",
          f"Um veto total aposto ao projeto está na lista de vetos do {_CN}, e o projeto não foi transformado "
          "em lei: o projeto foi vetado integralmente."),
    _rule("bill.04", _BILL, "camara", "ultimoStatus_descricaoSituacao", "Retirado pelo(a) Autor(a)", "withdrawn",
          "Situação Retirado pelo(a) Autor(a) na Câmara dos Deputados: o Poder Executivo retirou o projeto."),
    _rule("bill.05", _BILL, "camara", "ultimoStatus_descricaoSituacao", "Arquivada", "archived",
          "Situação Arquivada na Câmara dos Deputados: o projeto foi arquivado."),
    _rule("bill.06", _BILL, "camara", "ultimoStatus_descricaoSituacao", None, "inProgress",
          "Qualquer outra situação na Câmara dos Deputados: o projeto segue em tramitação, e a situação oficial "
          "é publicada como está."),
]
_BY_ID = {r["id"]: r for r in STATUS_RULES}


class PresidencyError(Exception):
    """The sources cannot be written into the presidency directory; each line of the message names the record."""


def terms(local_day: str) -> list[dict]:
    """The terms whose start is on or before the build date (door 3)."""
    return [dict(t) for t in TERMS if t["start"] <= local_day]


def term_of(day: str, known=TERMS) -> str | None:
    for t in known:
        if t["start"] <= day <= t["end"]:
            return t["id"]
    return None


def _match(kind: str, value, act_id: str, field: str) -> dict:
    for r in STATUS_RULES:
        if r["kind"] == kind and r["officialValue"] is not None and r["officialValue"] == value:
            return r
    raise PresidencyError(f"{act_id}: {field} {value!r} matches no status rule")


def mp_status(record: dict, act_id: str) -> tuple[str, str]:
    """`(status, rule id)` of an MP from its Congress `/processo` record (door 4)."""
    value = record.get("siglaTipoDeliberacao")
    if value is None:
        if record.get("tramitando") == "Sim":
            return "pending", "mpv.09"
        raise PresidencyError(f"{act_id}: siglaTipoDeliberacao None with tramitando {record.get('tramitando')!r} "
                              "matches no status rule")
    rule = _match(_MP, value, act_id, "siglaTipoDeliberacao")
    return rule["status"], rule["id"]


def device_status(situacao, act_id: str) -> tuple[str, str]:
    rule = _match(_DEVICE, situacao, act_id, "Situacao")
    return rule["status"], rule["id"]


def veto_status(statuses: list[str]) -> tuple[str, str]:
    return ("pending", "veto.01") if "pending" in statuses else ("decided", "veto.02")


def bill_status(norma: str | None, camara_status: str, vetoed_totally: bool) -> tuple[str, str]:
    """Precedence of door 4: Senate law, Câmara law, total veto, withdrawn, archived, anything else."""
    if norma:
        rule = "bill.01"
    elif camara_status == CAMARA_LAW:
        rule = "bill.02"
    elif vetoed_totally:
        rule = "bill.03"
    elif camara_status == _BY_ID["bill.04"]["officialValue"]:
        rule = "bill.04"
    elif camara_status == _BY_ID["bill.05"]["officialValue"]:
        rule = "bill.05"
    else:
        rule = "bill.06"
    return _BY_ID[rule]["status"], rule


def joint_position(official, identifier: str) -> str:
    if official not in POSITIONS:
        raise PresidencyError(f"{identifier}: TipoVoto {official!r} is not in the position map")
    return POSITIONS[official]


def normalise(name: str) -> str:
    stripped = "".join(c for c in unicodedata.normalize("NFKD", name) if not unicodedata.combining(c))
    return " ".join(stripped.casefold().split())


def contains(period: dict, day: str) -> bool:
    """Whether a half-open `[start, end)` exercise period intersects the session day."""
    next_day = (date.fromisoformat(day) + timedelta(days=1)).isoformat()
    return period["start"] < f"{next_day}T00:00:00" and period["end"] > f"{day}T00:00:00"


class Resolver:
    """Joint-vote name -> member id of one house (door 6)."""

    def __init__(self, members: dict[str, list[dict]], aliases: list[dict]):
        self.ids = {house: {m["id"] for m in docs} for house, docs in members.items()}
        self.index = defaultdict(list)
        for house, docs in members.items():
            for m in docs:
                for mandate in m["mandates"]:
                    self.index[(house, normalise(m["name"]), mandate["uf"])].append((m["id"], mandate))
        self.aliases = {(a["house"], a["name"], a["uf"]): a["memberId"] for a in aliases}

    def resolve(self, house: str, name: str, uf: str, day: str) -> tuple[int | None, int]:
        """`(member id, 1)` for exactly one match, else `(None, number of matches)`."""
        alias = self.aliases.get((house, name, uf))
        if alias is not None:
            if alias not in self.ids.get(house, ()):
                raise PresidencyError(f"alias {house} {name}/{uf} points at member {alias}, absent from "
                                      f"{house}/members.json")
            return alias, 1
        found = {member for member, mandate in self.index.get((house, normalise(name), uf), [])
                 if any(contains(p, day) for p in mandate["exercisePeriods"])}
        return (found.pop(), 1) if len(found) == 1 else (None, len(found))


def load_aliases(path: Path = ALIASES) -> list[dict]:
    return json.loads(path.read_text(encoding="utf-8"))


def _int(value) -> int:
    return int(str(value).strip())


def _veto_block(result: dict) -> dict:
    return result["ResultadoVetoMateriaCN"]["Veto"]


def _act(id, kind, type, number, year, issued, term, summary, status, official, rule, status_at, law, stages,
         related, matter, scope, devices, url) -> dict:
    return {"id": id, "kind": kind, "type": type, "number": number, "year": year, "issuedAt": issued,
            "termId": term, "summary": summary, "status": status, "officialStatus": official, "statusRule": rule,
            "statusAt": status_at, "law": law, "stages": stages, "relatedActId": related, "vetoedMatter": matter,
            "vetoScope": scope, "devices": devices, "sourceUrl": url}


def executive_bills(propositions: list[dict], authors: list[dict]) -> list[dict]:
    """Câmara rows of type PL, PLP or PEC with an author row `Poder Executivo` / `30000` (AC 8)."""
    executive = {a["idProposicao"] for a in authors if (a["nomeAutor"], a["codTipoAutor"]) == EXECUTIVE}
    found = {}
    for row in propositions:
        if row["id"] in executive and row["siglaTipo"] in BILL_TYPES:
            found[row["id"]] = row
    return [found[k] for k in sorted(found, key=int)]


def camara_ids(propositions: list[dict]) -> dict[tuple[str, int, int], int]:
    return {(r["siglaTipo"], _int(r["numero"]), _int(r["ano"])): _int(r["id"])
            for r in propositions if r["numero"].strip() and r["ano"].strip()}


def assemble(local_day: str, mp_records: list[dict], vetoes: list[tuple[dict, dict]], device_docs: dict[str, dict],
             bills: list[dict], bill_processes: dict[str, list], propositions: list[dict],
             members: dict[str, list[dict]], aliases: list[dict]) -> dict:
    """Builds every presidency document.

    `vetoes` is `[(list entry, veto result)]`, `device_docs` maps a device `Codigo` to its votes response,
    `bill_processes` maps a Câmara bill id to its Senate `/processo` list.
    """
    known = terms(local_day)
    first = TERMS[0]["start"]
    acts, coverage = {}, {"excludedBeforeFirstTerm": 0, "missingCamaraStage": 0, "approvedWithoutLaw": 0,
                          "jointRollCallsWithoutVotes": 0}
    ids = camara_ids(propositions)

    def add(act):
        if act["id"] in acts:
            raise PresidencyError(f"{act['id']}: listed twice")
        acts[act["id"]] = act

    def term(day: str, act_id: str) -> str | None:
        if day < first:
            coverage["excludedBeforeFirstTerm"] += 1
            return None
        found = term_of(day, known)
        if found is None:
            raise PresidencyError(f"{act_id}: issued on {day}, outside every known term")
        return found

    for r in mp_records:
        m = re.fullmatch(r"MPV (\d+)/(\d{4})", r.get("identificacao") or "")
        if not m:
            raise PresidencyError(f"MP list: identificacao {r.get('identificacao')!r} is not an MPV")
        number, year = int(m[1]), int(m[2])
        act_id = f"mpv-{number}-{year}"
        issued = r["dataApresentacao"][:10]
        term_id = term(issued, act_id)
        if term_id is None:
            continue
        status, rule = mp_status(r, act_id)
        stages = []
        if ("MPV", number, year) in ids:
            stages.append({"house": "camara", "propositionId": ids[("MPV", number, year)]})
        else:
            coverage["missingCamaraStage"] += 1
        stages.append({"house": "senado", "propositionId": _int(r["id"])})
        law = r.get("normaGerada") or None
        if status in ("approved", "approvedAmended") and law is None:
            coverage["approvedWithoutLaw"] += 1
        add(_act(act_id, _MP, "MPV", number, year, issued, term_id, r.get("ementa"), status,
                 r.get("siglaTipoDeliberacao"), rule, None if status == "pending" else r.get("dataDeliberacao"),
                 law, stages, None, None, None, [], MP_URL.format(materia=r["codigoMateria"])))

    joint = []
    total_vetoed = set()
    for entry, result in vetoes:
        materia = entry["Materia"]
        number, year = _int(materia["Numero"]), _int(materia["Ano"])
        act_id = f"vet-{number}-{year}"
        issued = entry["DataPublicacao"][:10]
        term_id = term(issued, act_id)
        if term_id is None:
            continue
        block = _veto_block(result)
        page = VETO_URL.format(codigo=entry["Codigo"])
        vetoed = entry.get("MateriaVetada") or {}
        matter = {"type": vetoed["Sigla"], "number": _int(vetoed["Numero"]), "year": _int(vetoed["Ano"])}
        scope = "total" if entry.get("Total") == "Sim" else "partial"
        if scope == "total":
            total_vetoed.add((matter["type"], matter["number"], matter["year"]))
        raw_devices = as_list((block.get("Dispositivos") or {}).get("Dispositivo"))
        if not raw_devices:
            raise PresidencyError(f"{act_id}: the veto result lists no device")
        devices, sessions = [], []
        for d in raw_devices:
            status, rule = device_status(d.get("Situacao"), act_id)
            identifier = d["Identificador"]
            voted = d.get("PossuiVotos") == "Sim"
            devices.append({"identifier": identifier, "description": d.get("Descricao"), "text": d.get("Conteudo"),
                            "reason": d.get("RazaoVeto"), "status": status, "officialStatus": d["Situacao"],
                            "statusRule": rule, "jointRollCallId": identifier if voted else None})
            if d.get("DataSessao"):
                sessions.append(d["DataSessao"][:10])
            if voted:
                joint.append(_joint(act_id, page, block, d, status, device_docs))
        status, rule = veto_status([d["status"] for d in devices])
        law = ((vetoed.get("NormaGerada") or {}).get("NomeNorma")) or None
        add(_act(act_id, _VETO, "VET", number, year, issued, term_id, materia.get("Ementa"), status, None, rule,
                 max(sessions) if status == "decided" and sessions else None, law, [], None, matter, scope,
                 devices, page))

    for row in bills:
        type_, number, year = row["siglaTipo"], _int(row["numero"]), _int(row["ano"])
        act_id = f"{type_.lower()}-{number}-{year}"
        issued = row["dataApresentacao"][:10]
        term_id = term(issued, act_id)
        if term_id is None:
            continue
        senate = sorted((p for p in bill_processes.get(row["id"], []) if p.get("casaIdentificadora") == "SF"),
                        key=lambda p: p["id"])
        norma = (senate[0].get("normaGerada") or None) if senate else None
        official = row["ultimoStatus_descricaoSituacao"] or None
        status, rule = bill_status(norma, row["ultimoStatus_descricaoSituacao"],
                                   (type_, number, year) in total_vetoed)
        stages = [{"house": "camara", "propositionId": _int(row["id"])}]
        if senate:
            stages.append({"house": "senado", "propositionId": _int(senate[0]["id"])})
        add(_act(act_id, _BILL, type_, number, year, issued, term_id, row.get("ementa") or None, status, official,
                 rule, row.get("ultimoStatus_dataHora", "")[:10] or None, norma, stages, None, None, None, [],
                 BILL_URL.format(id=row["id"])))

    for act in acts.values():
        matter = act["vetoedMatter"]
        if matter is not None:
            related = f"{matter['type'].lower()}-{matter['number']}-{matter['year']}"
            act["relatedActId"] = related if related in acts else None

    joint = [j for j in joint if j["actId"] in acts]
    coverage["jointRollCallsWithoutVotes"] = sum(1 for j in joint if not j["votesAvailable"])
    resolver = Resolver(members, aliases)
    _resolve_votes(joint, resolver)

    ordered = sorted(sorted(acts.values(), key=lambda a: a["id"]), key=lambda a: a["issuedAt"], reverse=True)
    joint = sorted(sorted(joint, key=lambda j: j["id"]), key=lambda j: j["date"], reverse=True)
    counts = member_counts(members, joint)
    term_rows = _term_rows(known, ordered)
    summary = {
        t["id"]: {
            "mps": sum(1 for a in ordered if a["termId"] == t["id"] and a["kind"] == _MP),
            "vetoes": sum(1 for a in ordered if a["termId"] == t["id"] and a["kind"] == _VETO),
            "devices": sum(len(a["devices"]) for a in ordered if a["termId"] == t["id"]),
            "bills": sum(1 for a in ordered if a["termId"] == t["id"] and a["kind"] == _BILL),
            "joint": sum(1 for j in joint if acts[j["actId"]]["termId"] == t["id"]),
        }
        for t in known
    }
    listing = [{k: v for k, v in j.items() if k != "votes"} for j in joint]
    files = {
        "acts.json": ordered,
        "status-rules.json": STATUS_RULES,
        "joint-roll-calls.json": listing,
        "member-veto-counts.json": counts,
    }
    files |= {f"joint-roll-calls/{j['id']}.json": j for j in joint if j["votesAvailable"]}
    return {
        "files": files,
        "terms": known,
        "coverage": {"terms": term_rows, "unmatchedVotes": {"camara": 0, "senado": 0}, **coverage},
        "summary": summary,
    }


def _joint(act_id: str, page: str, block: dict, device: dict, status: str, device_docs: dict) -> dict:
    identifier = device["Identificador"]
    if status not in RESULTS:
        raise PresidencyError(f"{identifier}: a device with votes has status {status}, not kept or overridden")
    method = METHODS.get(device.get("TipoVotacao"))
    if method is None:
        raise PresidencyError(f"{identifier}: TipoVotacao {device.get('TipoVotacao')!r} is not cedula or painel")
    day = device["DataSessao"][:10]
    record = {"id": identifier, "actId": act_id, "deviceIdentifier": identifier,
              "legislature": contract_v3.legislature_of(day), "date": day, "session": None, "method": method,
              "question": "keepVeto", "result": RESULTS[status], "tallies": None, "votesAvailable": False,
              "sourceUrl": page}
    codigo = device.get("Codigo")
    if not codigo:
        pdfs = as_list((block.get("PdfsResultadoVotacao") or {}).get("PdfResultadoVotacao"))
        if pdfs and pdfs[0].get("URL"):
            record["sourceUrl"] = pdfs[0]["URL"]
        return record
    votacao = device_docs[codigo]["ResultadoVetoDispositivoCN"]["Votacao"]
    votes, seen = [], set()
    for key, house in (("Camara", "camara"), ("Senado", "senado")):
        for v in as_list((votacao.get(key) or {}).get("Voto")):
            unique = (house, normalise(v["NomeParlamentar"]), v["UfParlamentar"])
            if unique in seen:
                raise PresidencyError(f"{identifier}: {house} {v['NomeParlamentar']}/{v['UfParlamentar']} "
                                      "voted twice")
            seen.add(unique)
            votes.append({"house": house, "memberId": None, "name": v["NomeParlamentar"],
                          "party": v["PartidoParlamentar"], "uf": v["UfParlamentar"], "official": v["TipoVoto"],
                          "position": joint_position(v["TipoVoto"], identifier)})
    votes.sort(key=lambda v: (v["house"], v["name"], v["uf"]))
    tallies = {house: {p: 0 for p in POSITIONS.values()} for house in HOUSES}
    for v in votes:
        tallies[v["house"]][v["position"]] += 1
    return {**record, "session": votacao.get("Sessao"), "tallies": tallies, "votesAvailable": True,
            "sourceUrl": DEVICE_URL.format(codigo=codigo), "votes": votes}


def _resolve_votes(joint: list[dict], resolver: Resolver) -> None:
    """Sets every `memberId`, or stops the build listing each unresolved (house, name, uf) (AC 31)."""
    failed = {}
    for j in sorted(joint, key=lambda j: (j["date"], j["id"])):
        for v in j.get("votes", []):
            member, matches = resolver.resolve(v["house"], v["name"], v["uf"], j["date"])
            v["memberId"] = member
            if member is None:
                failed.setdefault((v["house"], v["name"], v["uf"]), (j["date"], matches))
    if failed:
        lines = []
        for (house, name, uf), (day, matches) in sorted(failed.items()):
            what = "unmatched" if matches == 0 else f"ambiguous ({matches} members)"
            lines.append(f"{what} joint vote: {house} {name}/{uf}, first on {day}")
        raise PresidencyError("\n".join(lines))


def member_counts(members: dict[str, list[dict]], joint: list[dict]) -> list[dict]:
    """One row per (house, member, legislature) whose exercise holds a joint roll call with votes (door 6).

    The base holds only roll calls where the member's own house published per-member votes, so a house
    the Congress published nothing for (`Senado: null`) does not read as an absence (door 6, amended).
    """
    voted = [j for j in joint if j["votesAvailable"]]
    positions = defaultdict(lambda: defaultdict(list))  # (house, id, legislature) -> veto -> positions
    published = defaultdict(list)  # house -> roll calls with at least one vote of that house
    for j in voted:
        for v in j["votes"]:
            positions[(v["house"], v["memberId"], j["legislature"])][j["actId"]].append(v["position"])
        for house in {v["house"] for v in j["votes"]}:
            published[house].append(j)
    rows = []
    for house in HOUSES:
        for m in members.get(house, []):
            for mandate in m["mandates"]:
                legislature = mandate["legislature"]
                base = {j["actId"] for j in published[house] if j["legislature"] == legislature
                        and any(contains(p, j["date"]) for p in mandate["exercisePeriods"])}
                if not base:
                    continue
                own = positions.get((house, m["id"], legislature), {})
                took = [own[veto] for veto in sorted(base) if veto in own]
                keep = sum(1 for ps in took if all(p == "yes" for p in ps))
                override = sum(1 for ps in took if all(p == "no" for p in ps))
                n = len(took)
                rows.append({"house": house, "memberId": m["id"], "legislature": legislature,
                             "participation": {"count": n, "total": len(base)},
                             "keepAll": {"count": keep, "total": n}, "overrideAll": {"count": override, "total": n},
                             "mixed": {"count": n - keep - override, "total": n}})
    return sorted(rows, key=lambda r: (r["house"], r["memberId"], r["legislature"]))


def _term_rows(known: list[dict], acts: list[dict]) -> list[dict]:
    rows = []
    for t in known:
        mine = [a for a in acts if a["termId"] == t["id"]]

        def tally(kind, statuses):
            of_kind = [a for a in mine if a["kind"] == kind]
            return {"total": len(of_kind), **{s: sum(1 for a in of_kind if a["status"] == s) for s in statuses}}

        devices = [d for a in mine for d in a["devices"]]
        veto = tally(_VETO, VETO_STATUSES)
        veto["devices"] = {**{s: sum(1 for d in devices if d["status"] == s) for s in DEVICE_STATUSES},
                           "total": len(devices)}
        rows.append({"term": t["id"], "provisionalMeasure": tally(_MP, MP_STATUSES), "veto": veto,
                     "bill": tally(_BILL, BILL_STATUSES)})
    return rows

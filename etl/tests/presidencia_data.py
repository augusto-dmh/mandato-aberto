"""Presidency inputs for etl-presidencia, served by `conftest.FakeCamara`.

Three datasets carry the expected numbers of `.specs/features/etl-presidencia/checks.md`, computed on
paper next to the rows that produce them:

- `recorded()` (R): real Congress responses recorded on 2026-10-02 and trimmed, in
  `fixtures/v4/recorded/congresso/`, with hand Câmara rows (real ids where the API gave them) and
  hand house directories whose members carry the names the trimmed votes hold
- `hand()` (H): two hand vetoes for the member counts, homonyms, the alias and a total veto
- `terms()` (T): two MPs on each side of the 2027 inauguration, at the 2027 clock

The house directories are hand-written v4 documents that pass `etl/schema/v4/`.
"""

import copy
import json
from datetime import UTC, datetime
from pathlib import Path

from conftest import YEARLY, render

from mandato_etl import cli

RECORDED = Path(__file__).parent / "fixtures" / "v4" / "recorded" / "congresso"
CLOCK_2027 = datetime(2027, 3, 1, 12, 0, 0, tzinfo=UTC)  # 2027-03-01T09:00:00 in Brasília
PREFIX = "/dadosabertos"
WHOLE = [{"start": "2023-02-01T00:00:00", "end": "2026-09-27T09:00:00"}]
EXECUTIVE = {"codTipoAutor": "30000", "nomeAutor": "Poder Executivo", "tipoAutor": "Órgão do Poder Executivo"}


def recorded_doc(name: str):
    return json.loads((RECORDED / name).read_text())


# --- house directories ----------------------------------------------------------------------------

def _basis():
    return {"count": 0, "total": 0}


def member(id, name, party, uf, periods=WHOLE, legislature=57) -> dict:
    indicator = {"all": _basis(), "merit": _basis()}
    return {
        "house": None, "id": id, "name": name, "party": party, "uf": uf,
        "photoUrl": f"https://example.invalid/photo/{id}.jpg", "sourceUrl": f"https://example.invalid/member/{id}",
        "mandates": [{
            "legislature": legislature, "party": party, "uf": uf, "exercisePeriods": periods,
            "participation": indicator, "governmentAlignment": copy.deepcopy(indicator),
            "partyAlignment": copy.deepcopy(indicator), "symbolicMerit": None, "authoredCount": 0,
            "firstSignerCount": 0, "requirementsCount": 0,
        }],
    }


def roll_call(house, id, proposition, day="2023-05-31") -> dict:
    return {"house": house, "id": id, "legislature": 57, "date": day, "organ": "PLEN", "description": f"Votação {id}",
            "approved": True, "ballot": "nominal", "kind": "final", "kindRule": None, "propositionId": proposition,
            "tallies": {"yes": 0, "no": 0, "others": 0}, "governmentOrientation": None,
            "sourceUrl": f"https://example.invalid/roll-call/{id}"}


def house_dir(path: Path, house: str, members: list[dict], roll_calls: list[dict] = ()) -> None:
    path.mkdir(parents=True, exist_ok=True)
    meta = {"schema_version": 4, "house": house, "generatedAt": "2026-09-27T12:00:00Z",
            "legislatures": [{"id": 57, "start": "2023-02-01", "end": "2027-01-31",
                              "sourceUrl": "https://example.invalid/legislature/57"}],
            "coverage": [], "classification": {"version": 1}, "sources": []}
    docs = {"meta.json": meta, "members.json": [{**m, "house": house} for m in members],
            "roll-calls.json": list(roll_calls), "propositions.json": [], "classification-rules.json": []}
    for name, doc in docs.items():
        (path / name).write_text(json.dumps(doc, ensure_ascii=False))


# --- Câmara rows ----------------------------------------------------------------------------------

def prop(id, sigla, numero, ano, presented="", status="", status_at="", ementa=None) -> dict:
    return {"id": str(id), "siglaTipo": sigla, "numero": str(numero), "ano": str(ano),
            "ementa": ementa if ementa is not None else f"Ementa de {sigla} {numero}/{ano}.",
            "dataApresentacao": presented, "ultimoStatus_dataHora": status_at,
            "ultimoStatus_descricaoSituacao": status}


def author(id, **fields) -> dict:
    return {"idProposicao": str(id), **(fields or EXECUTIVE)}


# --- Congress shapes ------------------------------------------------------------------------------

def mp(number, year, id, materia, presented, deliberation=None, tramitando="Não", law=None, decided=None) -> dict:
    record = {"autoria": "Presidência da República", "casaIdentificadora": "CN", "codigoMateria": materia,
              "dataApresentacao": presented, "ementa": f"Ementa da MPV {number}/{year}.", "id": id,
              "identificacao": f"MPV {number}/{year}", "tramitando": tramitando}
    if deliberation is not None:
        record["siglaTipoDeliberacao"] = deliberation
    if law is not None:
        record["normaGerada"] = law
    if decided is not None:
        record["dataDeliberacao"] = decided
    return record


def veto_entry(number, year, codigo, materia, published, total, vetoed) -> dict:
    sigla, n, y = vetoed
    return {"Codigo": str(codigo),
            "Materia": {"Codigo": str(materia), "Sigla": "VET", "Numero": str(number), "Ano": str(year),
                        "Ementa": f"Veto aposto ao {sigla} {n}/{y}."},
            "MateriaVetada": {"Codigo": "1", "Sigla": sigla, "Numero": str(n), "Ano": str(y)},
            "Total": "Sim" if total else "Não", "DataPublicacao": published}


def veto_list(entries) -> dict:
    return {"ListaVetosAnoCN": {"Metadados": {}, "Vetos": {"Veto": list(entries)} if entries else {}}}


def device(identifier, situacao, codigo=None, tipo=None, day=None) -> dict:
    d = {"Identificador": identifier, "Descricao": f"dispositivo {identifier}", "Conteudo": f"Texto {identifier}",
         "RazaoVeto": f"Razão {identifier}", "Situacao": situacao, "PossuiVotos": "Sim" if tipo else "Não"}
    if codigo:
        d["Codigo"] = str(codigo)
    if tipo:
        d["TipoVotacao"], d["DataSessao"] = tipo, day
    return d


def veto_result(entry, devices, pdfs=()) -> dict:
    veto = {"Codigo": entry["Codigo"], "Materia": entry["Materia"], "Dispositivos": {"Dispositivo": list(devices)}}
    if pdfs:
        veto["PdfsResultadoVotacao"] = {"PdfResultadoVotacao": [{"URL": u, "Descricao": "Resultado"} for u in pdfs]}
    return {"ResultadoVetoMateriaCN": {"Metadados": {}, "Veto": veto}}


def device_votes(session, camara=(), senado=()) -> dict:
    def votes(rows):
        return {"Voto": [{"NomeParlamentar": n, "PartidoParlamentar": p, "UfParlamentar": u, "TipoVoto": v}
                         for n, p, u, v in rows]} if rows else None
    return {"ResultadoVetoDispositivoCN": {"Metadados": {}, "Votacao": {
        "ParteVetada": "x", "TipoVotacao": "Cédula", "Sessao": session, "Camara": votes(camara),
        "Senado": votes(senado)}}}


# --- datasets -------------------------------------------------------------------------------------

def recorded() -> dict:
    """R: see checks.md. Câmara rows are hand; the Congress responses are the recorded ones."""
    congress = {f"/processo?sigla=MPV&ano={y}": recorded_doc(f"processo-mpv-{y}.json") for y in (2023, 2026)}
    congress["/processo?sigla=MPV&ano=2025"] = []
    for y in (2023, 2025, 2026):
        congress[f"/materia/vetos/{y}"] = recorded_doc(f"vetos-{y}.json")
    for materia in ("158326", "161861", "166980", "169775", "172342"):
        congress[f"/plenario/resultado/veto/materia/{materia}"] = recorded_doc(f"veto-{materia}.json")
    for codigo in ("43265", "43825", "43828", "45468", "46051"):
        congress[f"/plenario/resultado/veto/dispositivo/{codigo}"] = recorded_doc(f"dispositivo-{codigo}.json")
    for name in ("pl-3626-2023", "plp-93-2023", "pl-6233-2023"):
        sigla, n, y = name.split("-")
        congress[f"/processo?sigla={sigla.upper()}&numero={n}&ano={y}"] = recorded_doc(f"processo-{name}.json")
    for sigla, n, y in (("PL", 1084, 2023), ("PL", 9101, 2023), ("PL", 9102, 2025)):
        congress[f"/processo?sigla={sigla}&numero={n}&ano={y}"] = []
    congress["/processo?sigla=PEC&numero=9103&ano=2026"] = [
        {"id": 9999103, "identificacao": "PEC 9103/2026", "casaIdentificadora": "CD", "tramitando": "Sim"}]
    camara = {
        2023: {
            "proposicoes": [
                prop(2345493, "MPV", 1154, 2023, "2023-01-01T17:36", "Aguardando Despacho do Presidente da Câmara dos "
                     "Deputados", "2023-06-20T10:00"),
                prop(2345494, "MPV", 1155, 2023, "2023-01-02T10:00", "Perdeu a Eficácia", "2023-06-01T10:00"),
                prop(2355224, "MPV", 1169, 2023, "2023-04-06T10:00", "", ""),
                prop(2367600, "MPV", 1177, 2023, "2023-06-06T10:00", "Aguardando Encaminhamento", "2023-10-11T10:00"),
                prop(2374255, "MPV", 1181, 2023, "2023-07-18T10:00", "Perdeu a Eficácia", "2023-11-01T10:00"),
                prop(2374400, "PL", 3626, 2023, "2023-07-25T17:48", "Transformado em Norma Jurídica",
                     "2024-06-04T00:00", ementa="Dispõe sobre a modalidade lotérica denominada apostas de quota fixa."),
                prop(2357053, "PLP", 93, 2023, "2023-04-18T19:48", "Transformado em Norma Jurídica", "2025-02-01T00:00"),
                prop(2416729, "PL", 6233, 2023, "2023-12-21T15:00", "Aguardando Encaminhamento", "2024-07-01T10:00"),
                prop(2351177, "PL", 1084, 2023, "2023-03-13T13:33", "Transformado em Norma Jurídica",
                     "2023-07-06T16:36"),
                prop(2345485, "PL", 1, 2023, "2022-12-30T11:38", "Retirado pelo(a) Autor(a)", "2023-05-29T00:00"),
                prop(9900101, "PL", 9101, 2023, "2023-05-02T10:00", "Retirado pelo(a) Autor(a)", "2023-08-01T10:00"),
                prop(9900201, "MSC", 9201, 2023, "2023-02-01T10:00", "Aguardando Despacho", "2023-02-02T10:00"),
                prop(9900202, "PLN", 9202, 2023, "2023-02-01T10:00", "Aguardando Despacho", "2023-02-02T10:00"),
                prop(9900203, "PL", 9203, 2023, "2023-02-01T10:00", "Aguardando Despacho", "2023-02-02T10:00"),
                prop(9900204, "PL", 9204, 2023, "2023-02-01T10:00", "Aguardando Despacho", "2023-02-02T10:00"),
            ],
            "proposicoesAutores": [
                *(author(i) for i in (2345493, 2345494, 2355224, 2367600, 2374255, 2374400, 2357053, 2416729,
                                      2351177, 2345485, 9900101, 9900201, 9900202)),
                author(9900203, codTipoAutor="10000", nomeAutor="Fulano Deputado", tipoAutor="Deputado(a)"),
                author(9900204, codTipoAutor="40000", nomeAutor="Poder Executivo", tipoAutor="Outro"),
            ],
        },
        2025: {
            "proposicoes": [prop(9900102, "PL", 9102, 2025, "2025-03-03T10:00", "Arquivada", "2025-09-01T10:00")],
            "proposicoesAutores": [author(9900102)],
        },
        2026: {
            "proposicoes": [prop(9900103, "PEC", 9103, 2026, "2026-01-05T10:00",
                                 "Aguardando Parecer do Relator na Comissão Especial (CESP)", "2026-05-01T10:00")],
            "proposicoesAutores": [author(9900103)],
        },
    }
    houses = {
        "camara": ([
            member(7001, "Adriana Ventura", "NOVO", "SP"),
            member(7002, "Afonso Hamm", "PP", "RS"),
            member(7003, "Airton Faleiro", "PT", "PA"),
            member(7004, "AJ  Albuquerque", "PP", "CE", [{"start": "2023-02-01T00:00:00", "end": "2025-01-01T00:00:00"}]),
            member(7005, "Alexandre Guimarães", "MDB", "TO"),
            member(7006, "Bruna Sem Voto", "PT", "SP"),
            member(7007, "Carlos Antigo", "PL", "RJ", [{"start": "2023-02-01T00:00:00", "end": "2023-06-01T00:00:00"}]),
        ], [
            roll_call("camara", "2345493-41", 2345493), roll_call("camara", "2345493-64", 2345493),
            roll_call("camara", "2374400-10", 2374400), roll_call("camara", "9999-1", 9999),
        ]),
        "senado": ([
            member(8001, "Alessandro Vieira", "MDB", "SE"),
            member(8002, "Ciro Nogueira", "PP", "PI"),
            member(8003, "Confúcio Moura", "MDB", "RO"),
            member(8004, "Davi Alcolumbre", "UNIÃO", "AP"),
            member(8005, "Dr. Hiran", "PP", "RR"),
            member(5386, "Professora Dorinha Seabra", "UNIÃO", "TO"),
            member(285, "Marcio Bittar", "PL", "AC"),
        ], [roll_call("senado", "6704", 8349431), roll_call("senado", "7000", 8463489)]),
    }
    return {"congress": congress, "camara": camara, "houses": houses, "years": ("2023", "2025", "2026")}


H90 = veto_entry(90, 2025, 19090, 190090, "2025-01-20", False, ("PL", 8000, 2024))
H91 = veto_entry(91, 2025, 19091, 190091, "2025-02-03", True, ("PL", 9105, 2025))


def hand() -> dict:
    """H: see checks.md."""
    s1, s2 = "Sessão Conjunta nº 1 de 10/03/2025", "Sessão Conjunta nº 2 de 15/04/2025"
    congress = {
        "/processo?sigla=MPV&ano=2025": [mp(1290, 2025, 9100001, 191001, "2025-02-10", "APROVADO_NA_INTEGRA",
                                            decided="2025-06-01")],
        "/materia/vetos/2025": veto_list([H90, H91]),
        "/plenario/resultado/veto/materia/190090": veto_result(H90, [
            device("90.25.001", "Rejeitado", 49001, "Cédula", "2025-03-10"),
            device("90.25.002", "Mantido", 49002, "Cédula", "2025-03-10"),
            device("90.25.003", "Mantido", 49003, "Painel", "2025-04-15"),
        ]),
        "/plenario/resultado/veto/materia/190091": veto_result(H91, [
            device("91.25.000", "Mantido", None, "Painel", "2025-04-15")]),
        # 90.25.001: camara yes 7101, 7103; no 7102 | senado yes 8101; no 5386
        "/plenario/resultado/veto/dispositivo/49001": device_votes(s1, [
            ("Ana Lima", "PT", "SP", "Sim"), ("Bruno Reis", "PL", "RJ", "Não"), ("Carla Dias", "MDB", "MG", "Sim"),
        ], [("Fernando Carvalho", "PSD", "SE", "Sim"), ("Prof. Dorinha Seabra", "UNIÃO", "TO", "Não")]),
        # 90.25.002: camara yes 7101; no 7102, 7103 | senado yes 8101, 5386
        "/plenario/resultado/veto/dispositivo/49002": device_votes(s1, [
            ("Ana Lima", "PT", "SP", "Sim"), ("Bruno Reis", "PL", "RJ", "Não"), ("Carla Dias", "MDB", "MG", "Não"),
        ], [("Fernando Carvalho", "PSD", "SE", "Sim"), ("Prof. Dorinha Seabra", "UNIÃO", "TO", "Sim")]),
        # 90.25.003: camara yes 7101, 7104; no 7102 | senado no 8102, 5386
        "/plenario/resultado/veto/dispositivo/49003": device_votes(s2, [
            ("Ana Lima", "PT", "SP", "Sim"), ("Bruno Reis", "PL", "RJ", "Não"), ("Davi Souza", "PSB", "BA", "Sim"),
        ], [("Fernando Carvalho", "PSD", "SE", "Não"), ("Prof. Dorinha Seabra", "UNIÃO", "TO", "Não")]),
        "/processo?sigla=PL&numero=9105&ano=2025": [],
    }
    camara = {2025: {
        "proposicoes": [prop(9800001, "MPV", 1290, 2025, "2025-02-10T10:00", "", ""),
                        prop(9800105, "PL", 9105, 2025, "2025-01-05T10:00", "Aguardando Sanção", "2025-01-20T10:00")],
        "proposicoesAutores": [author(9800001), author(9800105)],
    }}
    houses = {
        "camara": ([
            member(7101, "Ana Lima", "PT", "SP"),
            member(7102, "Bruno Reis", "PL", "RJ"),
            member(7103, "Carla Dias", "MDB", "MG"),
            member(7104, "Davi Souza", "PSB", "BA", [{"start": "2025-04-01T00:00:00", "end": "2026-09-27T09:00:00"}]),
            member(7105, "Eva Rocha", "PT", "GO"),
        ], []),
        "senado": ([
            member(8101, "Fernando Carvalho", "PSD", "SE",
                   [{"start": "2023-02-01T00:00:00", "end": "2025-03-20T00:00:00"}]),
            member(8102, "Fernando Carvalho", "PSD", "SE",
                   [{"start": "2025-03-20T00:00:00", "end": "2026-09-27T09:00:00"}]),
            member(5386, "Professora Dorinha Seabra", "UNIÃO", "TO"),
        ], []),
    }
    return {"congress": congress, "camara": camara, "houses": houses, "years": ("2025",)}


def terms() -> dict:
    """T: build at `CLOCK_2027` with `--years 2027`."""
    congress = {
        "/processo?sigla=MPV&ano=2027": [mp(1500, 2027, 9200001, 192001, "2027-01-04", tramitando="Sim"),
                                         mp(1501, 2027, 9200002, 192002, "2027-01-05", tramitando="Sim")],
        "/materia/vetos/2027": veto_list([]),
    }
    return {"congress": congress, "camara": {}, "houses": {"camara": ([], []), "senado": ([], [])},
            "years": ("2027",)}


# --- serving and building -------------------------------------------------------------------------

def v4(fake) -> Path:
    return fake.raw.parent / "v4"


def out(fake) -> Path:
    return v4(fake) / "presidencia"


def serve(fake, data: dict, houses: bool = True) -> None:
    for path, doc in data["congress"].items():
        fake.routes[PREFIX + path] = json.dumps(doc, ensure_ascii=False).encode()
    for year in data["years"]:
        rows = data["camara"].get(int(year), {})
        for name in YEARLY:
            fake.routes[fake.bulk_path(name, year)] = render(name, rows.get(name, []))
    fake.routes["/arquivos/deputados/csv/deputados.csv"] = render("deputados", [])
    if houses:
        for house, (members, roll_calls) in data["houses"].items():
            house_dir(v4(fake) / house, house, members, roll_calls)


def build4(fake, data: dict, *args) -> int:
    return cli.main(["build", "--contract", "4", "--house", "presidencia", "--years", *data["years"],
                     "--out", str(out(fake)), *args])


def load(fake, relative: str):
    return json.loads((out(fake) / relative).read_text())


def act(fake, id: str) -> dict:
    return next(a for a in load(fake, "acts.json") if a["id"] == id)


def device_of(fake, identifier: str) -> dict:
    return next(d for a in load(fake, "acts.json") for d in a["devices"] if d["identifier"] == identifier)


def joint(fake, id: str) -> dict:
    return next(j for j in load(fake, "joint-roll-calls.json") if j["id"] == id)


def congress_requests(fake) -> list[str]:
    return [p.removeprefix(PREFIX) for p, _ in fake.requests if p.startswith(PREFIX)]


def snapshot(path: Path) -> dict[str, bytes]:
    return {p.relative_to(path).as_posix(): p.read_bytes() for p in sorted(path.rglob("*")) if p.is_file()}


def patch(data: dict, path: str, edit) -> dict:
    """`data` with the Congress document at `path` changed in place by `edit(doc)`."""
    edit(data["congress"][path])
    return data

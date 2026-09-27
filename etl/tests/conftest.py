"""A local HTTP server standing in for the Câmara, and the hand-built legislature it serves.

Expected numbers in the tests were computed by hand from `legislature()` below; see the comments
next to each deputy's votes.
"""

import csv
import io
import json
import threading
import time
from datetime import UTC, datetime
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path

import pytest

from mandato_etl import cli
from mandato_etl.sources import camara

PINNED = datetime(2026, 9, 27, 12, 0, 0, tzinfo=UTC)  # 2026-09-27T09:00:00 in Brasília
YEARLY = camara.YEARLY

# Full column lists of the real files, so the allowlist has something to drop.
COLUMNS = {
    "votacoes": ["id", "uri", "data", "dataHoraRegistro", "idOrgao", "uriOrgao", "siglaOrgao", "idEvento", "uriEvento",
                 "aprovacao", "votosSim", "votosNao", "votosOutros", "descricao"],
    "votacoesVotos": ["idVotacao", "uriVotacao", "dataHoraVoto", "voto", "deputado_id", "deputado_uri", "deputado_nome",
                      "deputado_siglaPartido", "deputado_uriPartido", "deputado_siglaUf", "deputado_idLegislatura",
                      "deputado_urlFoto"],
    "votacoesOrientacoes": ["idVotacao", "uriVotacao", "siglaOrgao", "descricao", "siglaBancada", "uriBancada", "orientacao"],
    "votacoesProposicoes": ["idVotacao", "uriVotacao", "data", "descricao", "proposicao_id", "proposicao_uri",
                            "proposicao_titulo", "proposicao_ementa", "proposicao_codTipo", "proposicao_siglaTipo",
                            "proposicao_numero", "proposicao_ano"],
    "proposicoes": ["id", "uri", "siglaTipo", "numero", "ano", "codTipo", "descricaoTipo", "ementa", "ementaDetalhada",
                    "keywords", "dataApresentacao", "uriOrgaoNumerador", "ultimoStatus_dataHora",
                    "ultimoStatus_descricaoSituacao"],
    "proposicoesAutores": ["idProposicao", "uriProposicao", "idDeputadoAutor", "uriAutor", "codTipoAutor", "tipoAutor",
                           "nomeAutor", "siglaPartidoAutor", "uriPartidoAutor", "siglaUFAutor", "ordemAssinatura",
                           "proponente"],
    "deputados": ["uri", "nome", "idLegislaturaInicial", "idLegislaturaFinal", "nomeCivil", "cpf", "siglaSexo",
                  "urlRedeSocial", "urlWebsite", "dataNascimento", "dataFalecimento", "ufNascimento",
                  "municipioNascimento"],
    "tse": ["DT_GERACAO", "ANO_ELEICAO", "SG_UF", "DS_CARGO", "NR_CANDIDATO", "NM_CANDIDATO", "NM_URNA_CANDIDATO",
            "NR_CPF_CANDIDATO", "SG_PARTIDO", "DS_SITUACAO_CANDIDATURA", "DT_NASCIMENTO"],
}

CPF_IN_DEPUTADOS = "52998224725"
CPF_IN_TSE = "11144477735"


def render(kind: str, rows: list[dict], encoding: str = "utf-8-sig") -> bytes:
    buffer = io.StringIO()
    writer = csv.writer(buffer, delimiter=";", quoting=csv.QUOTE_ALL, lineterminator="\n")
    writer.writerow(COLUMNS[kind])
    for row in rows:
        writer.writerow([row.get(c, "") for c in COLUMNS[kind]])
    return buffer.getvalue().encode(encoding)


# Roll calls: R* in the plenary, C* in committees. R0 predates the legislature, R5 has no vote.
R0, R1, R2, R3, R4, R5 = "100-0", "100-1", "100-2", "100-3", "100-4", "100-5"
CX, CZ, CY = "200-1", "200-2", "200-3"
ROLL_CALLS = {
    R0: ("2023-01-31T15:00:00", "PLEN", "1"),
    R1: ("2023-03-01T15:00:00", "PLEN", "1"),
    R2: ("2024-06-01T15:00:00", "PLEN", "0"),
    R3: ("2024-09-01T15:00:00", "PLEN", ""),
    R4: ("2025-06-01T15:00:00", "PLEN", "1"),
    R5: ("2023-05-01T15:00:00", "PLEN", "1"),
    CX: ("2023-04-01T10:00:00", "CCJC", "1"),
    CZ: ("2023-05-15T10:00:00", "CCJC", "1"),
    CY: ("2025-03-01T10:00:00", "CFT", "0"),
}

# Only in `legislature(secret=True)`: a secret ballot, every record with an empty vote, official totals 12/5/2.
# 101 and 102 are in exercise on its date, 103 is not -> participation 4/5 for 101, 3/3 for 102.
R6 = "100-6"
SECRET = {R6: ("2025-08-01T15:00:00", "PLEN", "1")}
SECRET_VOTES = [("101", R6, ""), ("102", R6, ""), ("103", R6, "")]

DEPUTIES = {
    "101": ("Ana Souza", "PT", "SP", "ANA MARIA SOUZA", "1970-05-01"),
    "102": ("Bruno Lima", "PT", "RJ", "JOSÉ BRUNO LIMA", "1980-02-02"),
    "103": ("Carla Dias", "NOVO", "MG", "CARLA DIAS", "1990-03-03"),
    "104": ("Dário Velho", "PSDB", "BA", "DARIO VELHO", "1950-01-01"),
}

VOTES = [
    # 101, in exercise the whole legislature. PLEN: R1 R2 R4 recorded, R3 empty -> participation 3/4.
    # Government: R1 Sim=Sim, R2 Não=Não, CY Não!=Sim, R4 Liberado excluded -> 2/3.
    # Party (PT, others = 102): R1 Sim=Sim, CX Sim=Sim, CZ Abstenção!=Não -> 2/3.
    ("101", R0, "Sim"), ("101", R1, "Sim"), ("101", R2, "Não"), ("101", R3, ""), ("101", R4, "Obstrução"),
    ("101", CX, "Sim"), ("101", CZ, "Abstenção"), ("101", CY, "Não"),
    # 102, on leave 2024-03-01 to 2025-01-10: PLEN in exercise = R1, R4 -> participation 2/2 (R4 is Artigo 17).
    # Government: R1 Sim=Sim; R4 Artigo 17 excluded -> 1/1.
    ("102", R1, "Sim"), ("102", R4, "Artigo 17"), ("102", CX, "Sim"), ("102", CZ, "Não"),
    # 103, never in exercise per history, alone in NOVO -> participation 0/0, party 0/0, government 0/1.
    # Its R3 vote keeps R3 open: a roll call whose every record is empty is a secret ballot (R3 has no Governo).
    ("103", R1, "Não"), ("103", R3, "Não"),
]


def _vote_row(dep, rc, vote, legislature="57", seconds=5):
    name, party, uf, _, _ = DEPUTIES[dep]
    return {
        "idVotacao": rc, "uriVotacao": f"https://dadosabertos.camara.leg.br/api/v2/votacoes/{rc}",
        "dataHoraVoto": {**ROLL_CALLS, **SECRET}[rc][0][:-2] + f"{seconds:02d}", "voto": vote,
        "deputado_id": dep, "deputado_uri": f"https://dadosabertos.camara.leg.br/api/v2/deputados/{dep}",
        "deputado_nome": name, "deputado_siglaPartido": party, "deputado_siglaUf": uf,
        "deputado_idLegislatura": legislature,
        "deputado_urlFoto": f"https://www.camara.leg.br/internet/deputado/bandep/{dep}.jpg",
    }


PROPOSITIONS = [
    # id, type, presented, author rows (deputy, ordemAssinatura, proponente)
    ("6001", "PL", "2023-03-10T10:00:00", [("101", "1", "1"), ("102", "2", "1")]),
    ("6002", "PLP", "2023-03-11T10:00:00", [("101", "2", "1")]),
    ("6003", "PEC", "2023-03-12T10:00:00", [("101", "1", "1")]),
    ("6004", "PDL", "2023-03-13T10:00:00", [("101", "3", "1")]),
    ("6005", "PRC", "2023-03-14T10:00:00", [("101", "2", "1")]),
    ("6006", "REQ", "2023-03-15T10:00:00", [("101", "1", "1")]),
    ("6007", "RIC", "2023-03-16T10:00:00", [("101", "1", "1")]),
    ("6008", "INC", "2023-03-17T10:00:00", [("101", "1", "1")]),
    ("6009", "EMC", "2023-03-18T10:00:00", [("101", "1", "1")]),
    ("6010", "PL", "2023-01-20T10:00:00", [("101", "1", "1")]),
    ("6011", "PL", "2023-04-01T10:00:00", [("101", "1", "0")]),
    ("6012", "PL", "2023-04-02T10:00:00", [("", "1", "1")]),
]


def legislature(secret: bool = False) -> dict:
    """Source rows by kind, plus the API responses, for the hand-built 57th legislature.

    `secret=True` adds the secret ballot `R6`; every other row is the same.
    """
    rows = {
        "votacoes": [
            {"id": rc, "data": at[:10], "dataHoraRegistro": at, "siglaOrgao": organ, "aprovacao": approved,
             "votosSim": "9", "votosNao": "9", "votosOutros": "9", "descricao": f" Votação {rc} "}
            for rc, (at, organ, approved) in ROLL_CALLS.items()
        ],
        "votacoesVotos": [_vote_row(dep, rc, vote) for dep, rc, vote in VOTES] + [_vote_row("104", R0, "Sim", "56")],
        "votacoesOrientacoes": [
            {"idVotacao": R0, "siglaBancada": "Governo", "orientacao": "Sim"},
            {"idVotacao": R1, "siglaBancada": "GOVERNO", "orientacao": "Sim"},
            {"idVotacao": R1, "siglaBancada": "PT", "orientacao": "Sim"},
            {"idVotacao": R2, "siglaBancada": "Governo", "orientacao": "Não"},
            {"idVotacao": R3, "siglaBancada": "PT", "orientacao": "Sim"},
            {"idVotacao": R4, "siglaBancada": "Governo", "orientacao": "Liberado"},
            {"idVotacao": CY, "siglaBancada": "Governo", "orientacao": "Sim"},
        ],
        "votacoesProposicoes": [
            {"idVotacao": R1, "proposicao_id": "5001", "proposicao_titulo": "PL 1/2023", "proposicao_ementa": "Dispõe sobre X."},
            {"idVotacao": R1, "proposicao_id": "5002", "proposicao_titulo": "PL 2/2023", "proposicao_ementa": "Outra."},
            {"idVotacao": R2, "proposicao_id": "5003", "proposicao_titulo": "PEC 3/2024", "proposicao_ementa": ""},
        ],
        "proposicoes": [
            {"id": pid, "siglaTipo": kind, "numero": pid[-2:], "ano": presented[:4], "ementa": f"Ementa {pid}",
             "dataApresentacao": presented, "ultimoStatus_descricaoSituacao": "Aguardando Parecer"}
            for pid, kind, presented, _ in PROPOSITIONS
        ],
        "proposicoesAutores": [
            {"idProposicao": pid, "idDeputadoAutor": dep, "ordemAssinatura": order, "proponente": proponent}
            for pid, _, _, authors in PROPOSITIONS for dep, order, proponent in authors
        ],
        "deputados": [
            {"uri": f"https://dadosabertos.camara.leg.br/api/v2/deputados/{dep}", "nome": name, "nomeCivil": civil,
             "cpf": CPF_IN_DEPUTADOS if dep == "101" else "", "dataNascimento": born, "siglaSexo": "F"}
            for dep, (name, _, _, civil, born) in DEPUTIES.items()
        ],
    }
    if secret:
        rows["votacoes"] += [
            {"id": rc, "data": at[:10], "dataHoraRegistro": at, "siglaOrgao": organ, "aprovacao": approved,
             "votosSim": "12", "votosNao": "5", "votosOutros": "2", "descricao": f" Votação {rc} "}
            for rc, (at, organ, approved) in SECRET.items()
        ]
        rows["votacoesVotos"] += [_vote_row(dep, rc, vote) for dep, rc, vote in SECRET_VOTES]
    histories = {
        "101": [("2023-02-01T12:05", "Exercício", 57)],
        "102": [("2022-12-01T10:00", "Exercício", 57), ("2023-02-01T12:05", "Exercício", 57),
                ("2024-03-01T00:00", "Licença", 57), ("2025-01-10T10:00", "Exercício", 57)],
        "103": [("2019-02-01T00:00", "Exercício", 56), ("2023-02-01T00:00", "SUPLENCIA", 57), ("2023-02-02T00:00", None, 57)],
    }
    api = {
        "/api/v2/deputados?itens=1000": {"dados": [{"id": 101, "email": "a@x"}, {"id": 102, "email": "b@x"}]},
    }
    for dep, entries in histories.items():
        api[f"/api/v2/deputados/{dep}/historico"] = {"dados": [
            {"dataHora": at, "situacao": status, "descricaoStatus": f"{status} desc", "idLegislatura": leg, "email": None}
            for at, status, leg in entries
        ]}
    return {"rows": rows, "api": api}


TSE_ROWS = [
    {"NM_CANDIDATO": "ANA MARIA SOUZA", "DT_NASCIMENTO": "01/05/1970", "SG_UF": "SP", "DS_CARGO": "DEPUTADO FEDERAL",
     "SG_PARTIDO": "PT", "NR_CANDIDATO": "1313", "DS_SITUACAO_CANDIDATURA": "APTO"},
    {"NM_CANDIDATO": "JOSE BRUNO LIMA", "DT_NASCIMENTO": "02/02/1980", "SG_UF": "RJ", "DS_CARGO": "SENADOR",
     "SG_PARTIDO": "PT", "NR_CANDIDATO": "131", "DS_SITUACAO_CANDIDATURA": "APTO"},
    {"NM_CANDIDATO": "CARLA DIAS", "DT_NASCIMENTO": "03/03/1990", "SG_UF": "MG", "DS_CARGO": "DEPUTADO FEDERAL",
     "SG_PARTIDO": "NOVO", "NR_CANDIDATO": "3030", "DS_SITUACAO_CANDIDATURA": "APTO"},
    {"NM_CANDIDATO": "CARLA DIAS", "DT_NASCIMENTO": "03/03/1990", "SG_UF": "MG", "DS_CARGO": "DEPUTADO ESTADUAL",
     "SG_PARTIDO": "NOVO", "NR_CANDIDATO": "30300", "DS_SITUACAO_CANDIDATURA": "APTO"},
    {"NM_CANDIDATO": "FULANO DE TAL", "DT_NASCIMENTO": "04/04/1985", "SG_UF": "BA", "DS_CARGO": "DEPUTADO FEDERAL",
     "SG_PARTIDO": "XYZ", "NR_CANDIDATO": "9999", "DS_SITUACAO_CANDIDATURA": "APTO"},
]


def write_tse(path: Path, rows=TSE_ROWS) -> Path:
    path.write_bytes(render("tse", [{**r, "NR_CPF_CANDIDATO": CPF_IN_TSE} for r in rows], encoding="latin-1"))
    return path


class FakeCamara:
    def __init__(self):
        self.routes: dict[str, bytes] = {}
        self.fail: dict[str, list] = {}  # path -> statuses (or "hang") answered before the route
        self.requests: list[tuple[str, str]] = []
        self.delay = 0.0
        self.in_flight = self.max_in_flight = 0
        self._lock = threading.Lock()
        fake = self

        class Handler(BaseHTTPRequestHandler):
            def do_GET(self):
                with fake._lock:
                    fake.requests.append((self.path, self.headers.get("User-Agent", "")))
                    fake.in_flight += 1
                    fake.max_in_flight = max(fake.max_in_flight, fake.in_flight)
                try:
                    time.sleep(fake.delay)
                    queue = fake.fail.get(self.path)
                    if queue:
                        action = queue.pop(0)
                        if action == "hang":
                            time.sleep(1.0)
                            return
                        self.send_error(action)
                    elif self.path in fake.routes:
                        body = fake.routes[self.path]
                        self.send_response(200)
                        self.send_header("Content-Length", str(len(body)))
                        self.end_headers()
                        self.wfile.write(body)
                    else:
                        self.send_error(404)
                except (BrokenPipeError, ConnectionResetError):
                    pass
                finally:
                    with fake._lock:
                        fake.in_flight -= 1

            def log_message(self, *args):
                pass

        self.server = ThreadingHTTPServer(("127.0.0.1", 0), Handler)
        self.server.daemon_threads = True
        self.base = f"http://127.0.0.1:{self.server.server_port}"
        threading.Thread(target=self.server.serve_forever, kwargs={"poll_interval": 0.02}, daemon=True).start()

    def bulk_path(self, name, year):
        return f"/arquivos/{name}/csv/{name}-{year}.csv"

    def serve(self, data: dict, years=(2023,)):
        """Publishes `data` in the 2023 files; other years get header-only files."""
        for year in years:
            for name in YEARLY:
                self.routes[self.bulk_path(name, year)] = render(name, data["rows"][name] if year == 2023 else [])
        self.routes["/arquivos/deputados/csv/deputados.csv"] = render("deputados", data["rows"]["deputados"])
        for path, doc in data["api"].items():
            self.routes[path] = json.dumps(doc).encode()

    def bulk_requests(self):
        return [p for p, _ in self.requests if p.startswith("/arquivos/")]


@pytest.fixture
def fake(tmp_path, monkeypatch):
    server = FakeCamara()
    monkeypatch.setattr(camara, "BULK_URL", server.base + "/arquivos/{name}/csv/{name}-{year}.csv")
    monkeypatch.setattr(camara, "DEPUTIES_URL", server.base + "/arquivos/deputados/csv/deputados.csv")
    monkeypatch.setattr(camara, "API_URL", server.base + "/api/v2")
    monkeypatch.setattr(camara, "TIMEOUT", 0.3)
    server.sleeps = []
    monkeypatch.setattr(camara, "sleep", server.sleeps.append)
    monkeypatch.setattr(cli, "RAW_DIR", tmp_path / "raw")
    monkeypatch.setattr(cli, "now", lambda: PINNED)
    server.raw = tmp_path / "raw"
    server.out = tmp_path / "out"
    yield server
    server.server.shutdown()


def build(fake, *args, years=("2023",)) -> int:
    return cli.main(["build", "--years", *years, "--out", str(fake.out), *args])


def load(out: Path, relative: str):
    return json.loads((out / relative).read_text())


@pytest.fixture
def built(fake):
    """The hand-built legislature served and built once, with the TSE fixture."""
    fake.serve(legislature())
    tse = write_tse(fake.raw.parent / "consulta_cand_2026_BRASIL.csv")
    assert build(fake, "--tse-csv", str(tse)) == 0
    return fake

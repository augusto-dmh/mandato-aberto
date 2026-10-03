"""Senate inputs for etl-senado, served by `conftest.FakeCamara` in the shapes the Senate API returns.

Three datasets carry the expected numbers of `.specs/features/etl-senado/checks.md`; the numbers were
computed on paper and sit next to the rows that produce them. Recorded responses (2026-10-02, trimmed,
personal values replaced by sentinels) live in `fixtures/v3/recorded/senado/`.
"""

import json
from datetime import UTC, datetime
from pathlib import Path

from mandato_etl import cli

RECORDED = Path(__file__).parent / "fixtures" / "v3" / "recorded" / "senado"
CLOCK_2027 = datetime(2027, 3, 1, 12, 0, 0, tzinfo=UTC)  # 2027-03-01T09:00:00 in Brasília
PREFIX = "/dadosabertos"

# Personal values that must never reach the output (AC 13).
SENTINELS = {
    "NomeCompletoParlamentar": "SENTINEL-NOME-COMPLETO",
    "SexoParlamentar": "SENTINEL-SEXO",
    "EmailParlamentar": "sentinel@example.invalid",
    "Telefones": {"Telefone": {"NumeroTelefone": "SENTINEL-TELEFONE"}},
    "DataNascimento": "1900-01-01",
    "Naturalidade": "SENTINEL-NATURALIDADE",
    "EnderecoParlamentar": "SENTINEL-ENDERECO",
}


def recorded(name: str):
    return json.loads((RECORDED / name).read_text())


def period(n: int) -> dict:
    start, end = {56: ("2019-02-01", "2023-01-31"), 57: ("2023-02-01", "2027-01-31"),
                  58: ("2027-02-01", "2031-01-31")}[n]
    return {"NumeroLegislatura": str(n), "DataInicio": start, "DataFim": end}


def senator(code, name, party, uf, exercises, legislatures=(57, 58), single=False) -> dict:
    """A `Parlamentar` of `/senador/lista/legislatura/<n>`; `exercises` = [(DataInicio, DataFim or None)]."""
    exercicio = [{"CodigoExercicio": str(i), "DataInicio": start, **({"DataFim": end, "SiglaCausaAfastamento": "RET",
                                                                       "DescricaoCausaAfastamento": "Retorno"}
                                                                      if end else {})}
                 for i, (start, end) in enumerate(exercises, 1)]
    suplente = [{"DescricaoParticipacao": "1º Suplente", "CodigoParlamentar": str(int(code) + 50),
                 "NomeParlamentar": "Suplente"}]
    mandato = {
        "CodigoMandato": "1", "UfParlamentar": uf,
        "PrimeiraLegislaturaDoMandato": period(legislatures[0]),
        "SegundaLegislaturaDoMandato": period(legislatures[1]),
        "DescricaoParticipacao": "Titular",
        "Suplentes": {"Suplente": suplente[0] if single else suplente},
        "Exercicios": {"Exercicio": exercicio[0] if single and len(exercicio) == 1 else exercicio},
    }
    identification = {
        "CodigoParlamentar": str(code), "NomeParlamentar": name, "NomeCompletoParlamentar": SENTINELS[
            "NomeCompletoParlamentar"], "SexoParlamentar": SENTINELS["SexoParlamentar"],
        "UrlFotoParlamentar": f"http://www.senado.leg.br/senadores/img/fotos-oficiais/senador{code}.jpg",
        "EmailParlamentar": SENTINELS["EmailParlamentar"], "SiglaPartidoParlamentar": party, "UfParlamentar": uf,
    }
    return {
        "IdentificacaoParlamentar": identification,
        "Telefones": SENTINELS["Telefones"], "DataNascimento": SENTINELS["DataNascimento"],
        "Naturalidade": SENTINELS["Naturalidade"], "EnderecoParlamentar": SENTINELS["EnderecoParlamentar"],
        "Mandatos": {"Mandato": mandato if single else [mandato]},
    }


def member_list(entries: list[dict]) -> dict:
    return {"ListaParlamentarLegislatura": {"Metadados": {}, "Parlamentares": {"Parlamentar": entries}}}


def current_list(entries=()) -> dict:
    return {"ListaParlamentarEmExercicio": {"Metadados": {}, "Parlamentares": {"Parlamentar": list(entries)}}}


def vote(code, name, party, uf, value) -> dict:
    return {"codigoParlamentar": int(code), "nomeParlamentar": name, "sexoParlamentar": SENTINELS["SexoParlamentar"],
            "siglaPartidoParlamentar": party, "siglaUFParlamentar": uf, "siglaVotoParlamentar": value,
            "descricaoVotoParlamentar": None}


def roll_call(rc, day, description, seq, votes, secret=False, totals=(None, None, None), session=None,
              process=None, materia=None, sigla="PL", numero="1", ano=2025, result="A") -> dict:
    """A `/votacao` record; `process` and `materia` default to `rc + 100` and `rc + 10100`."""
    return {
        "ano": ano, "casaSessao": "SF", "codigoMateria": materia or rc + 10100, "codigoSessao": session or rc + 400000,
        "codigoSessaoVotacao": rc, "dataApresentacao": "2025-01-02", "dataSessao": day,
        "descricaoVotacao": description, "ementa": f"Ementa {rc}.", "idProcesso": process or rc + 100,
        "identificacao": f"{sigla} {numero}/{ano}", "informeLegislativo": None, "numero": numero,
        "resultadoVotacao": result, "sequencialVotacao": seq, "sigla": sigla, "siglaTipoSessao": "DOR",
        "totalVotosSim": totals[0], "totalVotosNao": totals[1], "totalVotosAbstencao": totals[2],
        "votacaoSecreta": "S" if secret else "N", "votos": votes,
    }


def orientation(seq, entries) -> dict:
    return {"sequencialVotacao": seq, "codigoVotacaoSve": seq + 10000, "orientacoesLideranca": [
        {"dataHora": "2025-03-10T18:00:00", "partido": bench, "voto": value} for bench, value in entries],
        "votosParlamentar": [], "votosLideranca": []}


def process(pid, ident, autoria, presented, materia=None) -> dict:
    return {"autoria": autoria, "casaIdentificadora": "SF", "codigoMateria": materia or pid + 10000,
            "dataApresentacao": presented, "ementa": f"Ementa {ident}.", "id": pid, "identificacao": ident,
            "situacaoAtual": "EM TRAMITAÇÃO", "tipoDocumento": "Projeto", "tramitando": "Sim",
            "urlDocumento": f"https://legis.senado.gov.br/sdleg-getter/documento?dm={pid}"}


def detail(pid, codes) -> dict:
    return {"id": pid, "autoriaIniciativa": [
        {"autor": f"Autor {code}", "sexo": SENTINELS["SexoParlamentar"], "ordem": i, "codigoParlamentar": code}
        for i, code in enumerate(codes, 1)]}


def dataset(votacao, orientacao, lists, processos=None, details=None, atual=None) -> dict:
    """API documents by path under `/dadosabertos`.

    votacao / orientacao: {(first day, last day): [records]}; lists: {legislature: [Parlamentar]};
    processos: {(member, start): [process]}; details: {process id: [codigoParlamentar by ordem]}.
    """
    routes = {}
    for (first, last), records in votacao.items():
        routes[f"/votacao?dataInicio={first}&dataFim={last}"] = records
    for (first, last), items in orientacao.items():
        routes[f"/plenario/votacao/orientacaoBancada/{first.replace('-', '')}/{last.replace('-', '')}"] = {
            "votacoes": items}
    for n, entries in lists.items():
        routes[f"/senador/lista/legislatura/{n}?exercicio=S"] = member_list(entries)
    routes["/senador/lista/atual"] = atual or current_list()
    for (member, start), items in (processos or {}).items():
        routes[f"/processo?codigoParlamentarAutor={member}&dataInicioApresentacao={start}"] = items
    for pid, codes in (details or {}).items():
        routes[f"/processo/{pid}"] = detail(pid, codes)
    return routes


def serve(fake, routes: dict) -> None:
    for path, doc in routes.items():
        fake.routes[PREFIX + path] = json.dumps(doc, ensure_ascii=False).encode()


def build(fake, *args, years=("2025",)) -> int:
    return cli.main(["build", "--contract", "3", "--house", "senado", "--years", *years, "--out", str(out(fake)),
                     *args])


def out(fake) -> Path:
    return fake.raw.parent / "senado"


def load(fake, relative: str):
    return json.loads((out(fake) / relative).read_text())


def pin(monkeypatch, moment: datetime) -> None:
    monkeypatch.setattr(cli, "now", lambda: moment)


def requests(fake) -> list[str]:
    return [p.removeprefix(PREFIX) for p, _ in fake.requests]


def mandate(member: dict, legislature: int = 57) -> dict:
    return next(m for m in member["mandates"] if m["legislature"] == legislature)


def members_by_id(fake) -> dict[int, dict]:
    return {m["id"]: m for m in load(fake, "members.json")}


# --- indicators (S4). One legislature (57), `--years 2025`, clock 2026-09-27.

YEAR_2025 = ("2025-01-01", "2025-12-31")
SENATORS = {
    "9001": ("Ana Alves", "PT", "SP"),
    "9002": ("Beto Braga", "PT", "RJ"),
    "9003": ("Caio Costa", "PL", "MG"),
    "9004": ("Dani Dias", "S/Partido", "BA"),
    "9005": ("Eva Esteves", "S/Partido", "GO"),
}
V = {n: 6900 + n for n in range(1, 8)}  # V1 6901 .. V7 6907
ROLL_CALLS = [
    # (n, day, description, sequencialVotacao, secret)
    (1, "2025-03-10", "Votação nominal do Requerimento nº 1, de 2025.", 5001, False),  # procedural senado.01
    (2, "2025-03-11", "Votação nominal do Projeto de Lei nº 10, de 2025.", 5002, False),  # final senado.07
    (3, "2025-03-12", "Votação nominal da Emenda nº 3 ao Projeto de Lei nº 10, de 2025, destacada.", 5003, False),
    (4, "2025-03-13", "Mensagem nº 5, de 2025 - Fulano de Tal (ANTT).", 5004, True),  # final senado.06, 40/1/1
    (5, "2025-03-14", "Votação nominal da PEC nº 2/2025 (1º Turno).", 5005, False),  # final senado.07
    (6, "2025-03-15", "Votação nominal da Emenda nº 1 (Substitutivo) ao Projeto de Lei nº 11, de 2025.", None, False),
    (7, "2025-03-16", "Solicita urgência para o Projeto de Lei nº 12, de 2025", 5007, False),  # procedural senado.02
]
INDICATOR_VOTES = {
    # merit = V2 V3 V4 V5 V6. Government: V1 yes, V2 no, V3 free, V4 none, V5 yes, V6 none, V7 yes.
    # Ana, open period: participation all 5/7 (P-NRV, LS out), merit 4/5 (P-NRV out).
    # Government: V1 yes=yes, V2 yes!=no -> 1/2, merit 0/1. Party (PT, other = Beto): V1 x, V2 ok, V3 x -> 1/3, merit 1/2.
    "9001": ["Sim", "Sim", "Sim", "Votou", "Presidente (art. 51 RISF)", "P-NRV", "LS"],
    # Beto, period ends 2025-03-14T00:00 -> V1-V4 in period: participation 4/4, merit 3/3. V5 `AP` is outside (mismatch).
    # Government: V1 no!=yes, V2 yes!=no -> 0/2, merit 0/1. Party: V1 x, V2 ok, V3 x -> 1/3, merit 1/2.
    "9002": ["Não", "Sim", "Não", "Votou", "AP", None, None],
    # Caio, open: participation 6/7 (MIS out), merit 5/5. Government: V1 ok, V2 ok, V5 no!=yes -> 2/3, merit 1/2.
    # Alone in PL: party 0/0.
    "9003": ["Sim", "Não", "Abstenção", "Votou", "Não", "Sim", "MIS"],
    # Dani, open, no record in V3 (mismatch): participation 3/7 (V1 V2 V4), merit 2/5 (V2 V4).
    # Government: V1 ok, V2 x -> 1/2, merit 0/1. S/Partido: party 0/0.
    "9004": ["Sim", "Sim", None, "Votou", "NCom", "NA", "LP"],
    # Eva, open: participation 6/7 (LAP out), merit 5/5. Government: V1 ok, V2 x, V5 ok -> 2/3, merit 1/2.
    # S/Partido with Dani, both Sim in V1 and V2, yet partyMajority is null: party 0/0.
    "9005": ["Sim", "Sim", "Sim", "Votou", "Sim", "Sim", "LAP"],
}
ORIENTATIONS = [
    orientation(5001, [("Governo", "SIM"), ("PT", "SIM")]),
    orientation(5002, [("Governo", "NÃO"), ("PT", "SIM")]),
    orientation(5003, [("Governo", "LIVRE")]),
    orientation(5005, [("Governo", "SIM"), ("PL", "NÃO"), ("Republica", "OBSTRUÇÃO")]),
    orientation(5007, [("Governo", "SIM"), ("PT", None)]),
    orientation(4999, [("Governo", "SIM")]),  # a sequencial absent from /votacao
]
ANA_PROCESSES = [
    process(8001, "PL 10/2025", "Senadora Ana Alves (PT/SP)", "2025-02-10"),  # authored, first (single)
    process(8002, "PEC 2/2025", "Senador Caio Costa (PL/MG), Senadora Ana Alves (PT/SP)", "2025-02-11"),  # not first
    process(8003, "PL 20/2025", "Senadora Ana Alves (PT/SP), Senador Beto Braga (PT/RJ)", "2025-02-12"),  # first
    process(8004, "PL 2434/2019 (Substitutivo-CD)", "Câmara dos Deputados", "2025-05-09"),  # nowhere
    process(8005, "PLP 3/2025", "Senadora Ana Alves (PT/SP) e outros.", "2025-02-13"),  # first, via detail
    process(8006, "PDL 4/2025", "Senadora Ana Alves (PT/SP)", "2025-02-14"),  # authored, first
    process(8007, "PRS 5/2025", "Senadora Ana Alves (PT/SP)", "2025-02-15"),  # authored, first
    process(8008, "RQS 100/2025", "Senadora Ana Alves (PT/SP)", "2025-02-16"),  # requirement
    process(8009, "REQ 6/2025 - CDH", "Senadora Ana Alves (PT/SP)", "2025-02-17"),  # requirement
    process(8010, "INS 1/2025", "Senadora Ana Alves (PT/SP)", "2025-02-18"),  # requirement
    process(8011, "PL 30/2022", "Senadora Ana Alves (PT/SP)", "2022-12-01"),  # before the legislature: nowhere
    process(8012, "R.S 1/2025", "Senadora Ana Alves (PT/SP)", "2025-02-19"),  # nowhere
    process(8013, "VET 1/2025", "Presidência da República", "2025-02-20"),  # nowhere
    process(8014, "PL 40/2025", "Líder do Bloco Parlamentar Ana Alves (PT/SP)", "2025-02-21"),  # nowhere
]
# Ana 6 authored / 5 first / 3 requirements; Caio 1 / 1 / 0; Beto 1 / 0 / 0.
DETAILS = {8002: [9003, 9001], 8003: [9001, 9002], 8005: [9001, 5000]}


def indicators(votes=None, orientations=None, records_extra=(), extra_years=None) -> dict:
    votes = votes or INDICATOR_VOTES
    records = []
    for n, day, description, seq, secret in ROLL_CALLS:
        own = [vote(code, *SENATORS[code], values[n - 1]) for code, values in votes.items() if values[n - 1]]
        records.append(roll_call(V[n], day, description, seq, own, secret=secret,
                                 totals=(40, 1, 1) if secret else (None, None, None)))
    entries = [
        senator(9001, "Ana Alves", "PT", "SP", [("2023-02-01", None)]),
        senator(9002, "Beto Braga", "PT", "RJ", [("2023-02-01", "2025-03-13")]),
        senator(9003, "Caio Costa", "PL", "MG", [("2023-02-01", None)]),
        senator(9004, "Dani Dias", "S/Partido", "BA", [("2023-02-01", None)]),
        senator(9005, "Eva Esteves", "S/Partido", "GO", [("2023-02-01", None)]),
        senator(9006, "Fabio Farias", "PSD", "SP", [("2019-02-01", "2021-03-19")], legislatures=(56, 57)),
    ]
    start = "2023-02-01"
    votacao = {YEAR_2025: records + list(records_extra)}
    orientacao = {YEAR_2025: orientations if orientations is not None else ORIENTATIONS}
    for key, items in (extra_years or {}).items():
        votacao[key] = items
        orientacao[key] = []
    return dataset(
        votacao, orientacao, {57: entries},
        processos={("9001", start): ANA_PROCESSES, ("9002", start): [ANA_PROCESSES[2]],
                   ("9003", start): [ANA_PROCESSES[1]], ("9004", start): [], ("9005", start): []},
        details=DETAILS,
    )


# --- recorded (S3). `--years 2023 2025`, clock 2026-09-27, no member listed.

UNCLASSIFIED = 9999
BEFORE = 9998


def recorded_dataset(records_2025=None) -> dict:
    hand = roll_call(UNCLASSIFIED, "2025-12-18", "Votação nominal do Parecer nº 9, de 2025.", 4998, [],
                     process=9_000_001, materia=999_001, sigla="PRS", numero="9")
    before = roll_call(BEFORE, "2023-01-31", "Votação nominal do Projeto de Lei nº 1, de 2023.", 3001, [],
                       ano=2023)
    return dataset(
        {("2023-02-01", "2023-12-31"): [before, *recorded("votacao-2023.json")],
         YEAR_2025: (records_2025 if records_2025 is not None else recorded("votacao-2025.json")) + [hand]},
        {("2023-02-01", "2023-12-31"): recorded("orientacao-2023.json")["votacoes"],
         YEAR_2025: recorded("orientacao-2025.json")["votacoes"]},
        {57: []},
    )


# --- members (S2). Clock 2027-03-01 with `--years 2025 2027`, or 2026-09-27 with `--years 2025`.

M1, M2, M3 = 6801, 6802, 6803


def members_dataset(uf_9201_m3="SP") -> dict:
    real = {e["IdentificacaoParlamentar"]["CodigoParlamentar"]: e
            for e in recorded("legislatura-57.json")["ListaParlamentarLegislatura"]["Parlamentares"]["Parlamentar"]}
    single = senator(9201, "Nove Dois Zero Um", "PT", "SP", [("2023-02-01", None)], single=True)
    gone = senator(9202, "Nove Dois Zero Dois", "MDB", "RS", [("2019-02-01", "2021-03-19")], legislatures=(56, 57))
    ana_paula = ("6358", "Ana Paula Lobato", "PSB", "MA")
    m1 = roll_call(M1, "2025-05-01", "Votação nominal do Projeto de Lei nº 50, de 2025.", 6001, [
        vote(*ana_paula, "Sim"), vote(9201, "Nove Dois Zero Um", "PT", "SP", "Sim"),
        vote(9202, "Nove Dois Zero Dois", "MDB", "RS", "P-NRV")])
    m2 = roll_call(M2, "2027-01-20", "Votação nominal do Projeto de Lei nº 51, de 2026.", 6002, [
        vote(9201, "Nove Dois Zero Um", "PT", "SP", "Sim")], ano=2026)
    m3 = roll_call(M3, "2027-02-03", "Votação nominal do Projeto de Lei nº 52, de 2027.", 6003, [
        vote(9201, "Nove Dois Zero Um", "PSB", uf_9201_m3, "Não")], ano=2027)
    starts = {code: "2023-02-01" for code in ("4605", "6358", "9201", "9202")}
    return dataset(
        {YEAR_2025: [m1], ("2027-01-01", "2027-01-31"): [m2], ("2027-02-01", "2027-12-31"): [m3]},
        {YEAR_2025: [], ("2027-01-01", "2027-01-31"): [], ("2027-02-01", "2027-12-31"): []},
        {57: [real["4605"], real["6358"], real["5666"], single, gone], 58: [single, real["6358"]]},
        processos={(code, start): [] for code, start in starts.items()},
    )

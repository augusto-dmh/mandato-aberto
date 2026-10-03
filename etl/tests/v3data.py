"""Hand-built Câmara inputs for contract v3, served by `conftest.FakeCamara`.

Three datasets carry the expected numbers of `.specs/features/contract-v3/checks.md`; the numbers
were computed on paper and sit next to the rows that produce them.
"""

import json
from datetime import UTC, datetime
from pathlib import Path

from conftest import CPF_IN_DEPUTADOS

from mandato_etl import cli

V3_PINNED = datetime(2027, 3, 1, 12, 0, 0, tzinfo=UTC)  # 2027-03-01T09:00:00 in Brasília
FULL_TEXT_SENTENCE = "Art. 1 Esta lei institui o teste."


def pdf(text: str) -> bytes:
    """A one-page PDF whose text layer holds `text` (ASCII), with a correct xref table."""
    content = b"BT /F1 12 Tf 72 720 Td (" + text.encode("ascii") + b") Tj ET"
    objects = [
        b"<< /Type /Catalog /Pages 2 0 R >>",
        b"<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
        b"<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R "
        b"/Resources << /Font << /F1 5 0 R >> >> >>",
        b"<< /Length %d >>\nstream\n" % len(content) + content + b"\nendstream",
        b"<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
    ]
    out, offsets = b"%PDF-1.4\n", []
    for number, body in enumerate(objects, 1):
        offsets.append(len(out))
        out += b"%d 0 obj\n" % number + body + b"\nendobj\n"
    xref = len(out)
    out += b"xref\n0 %d\n0000000000 65535 f \n" % (len(objects) + 1)
    out += b"".join(b"%010d 00000 n \n" % o for o in offsets)
    out += b"trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF\n" % (len(objects) + 1, xref)
    return out


def legislature_by_date(day: str) -> str:
    return "57" if day <= "2027-01-31" else "58"


def dataset(deputies, roll_calls, votes, orientations=(), links=(), propositions=(), lists=None, histories=None,
            texts=None) -> dict:
    """Rows by bulk kind plus API documents.

    deputies: {id: (name, party, uf)}
    roll_calls: [{id, at, organ, desc, opening?, presentation?, totals?}]
    votes: [(dep, rc, value)] or [(dep, rc, value, overrides)] where overrides replace vote-row fields
    orientations: [(rc, bench, value)]; links: [(rc, prop, type, number, year, ementa)]
    propositions: [(prop, type, presented, [(dep, ordemAssinatura, proponente)])]
    lists: {legislature: [dep]}; histories: {dep: [(dataHora, situacao, idLegislatura)]}
    texts: {prop: True (a PDF is served) | False (`urlInteiroTeor` is null)}
    """
    at_of = {rc["id"]: rc["at"] for rc in roll_calls}

    def vote_row(dep, rc, value, overrides=None):
        name, party, uf = deputies[dep]
        row = {
            "idVotacao": rc, "dataHoraVoto": at_of[rc][:-2] + "05", "voto": value,
            "deputado_id": dep, "deputado_nome": name, "deputado_siglaPartido": party, "deputado_siglaUf": uf,
            "deputado_idLegislatura": legislature_by_date(at_of[rc][:10]),
            "deputado_urlFoto": f"https://www.camara.leg.br/internet/deputado/bandep/{dep}.jpg",
        }
        return row | (overrides or {})

    rows = {
        "votacoes": [
            {"id": rc["id"], "data": rc["at"][:10], "dataHoraRegistro": rc["at"], "siglaOrgao": rc["organ"],
             "aprovacao": "1", "votosSim": rc.get("totals", ("9", "9", "9"))[0],
             "votosNao": rc.get("totals", ("9", "9", "9"))[1], "votosOutros": rc.get("totals", ("9", "9", "9"))[2],
             "descricao": rc["desc"], "ultimaAberturaVotacao_descricao": rc.get("opening", ""),
             "ultimaApresentacaoProposicao_descricao": rc.get("presentation", "")}
            for rc in roll_calls
        ],
        "votacoesVotos": [vote_row(*v) for v in votes],
        "votacoesOrientacoes": [{"idVotacao": rc, "siglaBancada": bench, "orientacao": value}
                                for rc, bench, value in orientations],
        "votacoesProposicoes": [
            {"idVotacao": rc, "proposicao_id": prop, "proposicao_titulo": f"{kind} {number}/{year}",
             "proposicao_ementa": ementa, "proposicao_siglaTipo": kind, "proposicao_numero": number,
             "proposicao_ano": year}
            for rc, prop, kind, number, year, ementa in links
        ],
        "proposicoes": [
            {"id": prop, "siglaTipo": kind, "numero": prop[-2:], "ano": presented[:4], "ementa": f"Ementa {prop}",
             "dataApresentacao": presented, "ultimoStatus_descricaoSituacao": "Aguardando Parecer"}
            for prop, kind, presented, _ in propositions
        ],
        "proposicoesAutores": [
            {"idProposicao": prop, "idDeputadoAutor": dep, "ordemAssinatura": order, "proponente": proponent}
            for prop, _, _, authors in propositions for dep, order, proponent in authors
        ],
        "deputados": [
            {"uri": f"https://dadosabertos.camara.leg.br/api/v2/deputados/{dep}", "nome": name,
             "nomeCivil": name.upper(), "cpf": CPF_IN_DEPUTADOS, "dataNascimento": "1970-01-01"}
            for dep, (name, _, _) in deputies.items()
        ],
    }
    api = {"/api/v2/deputados?itens=1000": {"dados": [{"id": int(d)} for d in deputies]}}
    for legislature, members in (lists or {}).items():
        api[f"/api/v2/deputados?idLegislatura={legislature}&itens=1000"] = {"dados": [
            {"id": int(dep), "uri": f"https://dadosabertos.camara.leg.br/api/v2/deputados/{dep}",
             "nome": deputies[dep][0], "siglaPartido": deputies[dep][1], "siglaUf": deputies[dep][2],
             "idLegislatura": int(legislature), "email": f"dep.{dep}@camara.leg.br",
             "urlFoto": f"https://www.camara.leg.br/internet/deputado/bandep/{dep}.jpg"}
            for dep in members
        ]}
    for dep, entries in (histories or {}).items():
        api[f"/api/v2/deputados/{dep}/historico"] = {"dados": [
            {"dataHora": at, "situacao": status, "descricaoStatus": f"{status} desc", "idLegislatura": leg,
             "email": None}
            for at, status, leg in entries
        ]}
    return {"rows": rows, "api": api, "texts": texts or {}}


def serve(fake, data: dict) -> None:
    fake.serve(data)
    for prop, has_pdf in data["texts"].items():
        url = f"{fake.base}/inteiro-teor/{prop}.pdf" if has_pdf else None
        fake.routes[f"/api/v2/proposicoes/{prop}"] = json.dumps(
            {"dados": {"id": int(prop), "siglaTipo": "PL", "urlInteiroTeor": url}}).encode()
        if has_pdf:
            fake.routes[f"/inteiro-teor/{prop}.pdf"] = pdf(FULL_TEXT_SENTENCE)


def build3(fake, *args) -> int:
    return cli.main(["build", "--contract", "3", "--years", "2023", "--out", str(out3(fake)), *args])


def out3(fake) -> Path:
    return fake.raw.parent / "v3"


def load3(fake, relative: str):
    return json.loads((out3(fake) / relative).read_text())


def snapshot(out: Path) -> dict[str, bytes]:
    return {p.relative_to(out).as_posix(): p.read_bytes() for p in sorted(out.rglob("*")) if p.is_file()}


def api_requests(fake) -> list[str]:
    return [p for p, _ in fake.requests if not p.startswith("/arquivos/")]


def mandate(member: dict, legislature: int) -> dict:
    return next(m for m in member["mandates"] if m["legislature"] == legislature)


def member(fake, dep: int) -> dict:
    return next(m for m in load3(fake, "members.json") if m["id"] == dep)


def roll_call(fake, rc: str) -> dict:
    return next(r for r in load3(fake, "roll-calls.json") if r["id"] == rc)


EXERCISE = [("2023-02-01T10:00", "Exercício", 57)]
TRIO = {"201": ("Ana Souza", "PT", "SP"), "202": ("Beto Lima", "PT", "RJ"), "203": ("Caio Dias", "PL", "MG")}

# --- indicators (S4) ---------------------------------------------------------------------------
# Plenary: P1 P2 procedural, F1 F2 final, A1 amendment (all nominal), S1 final symbolic; C1 committee.
# 202 is on leave from 2024-01-01T00:00, so F2 (2024-02-01) and S1 (2024-03-01) are outside its periods.
P1, P2, F1, F2, A1, S1, C1 = "300-1", "300-2", "300-3", "300-4", "300-5", "300-6", "400-1"
INDICATOR_ROLL_CALLS = [
    {"id": P1, "at": "2023-03-01T15:00:00", "organ": "PLEN",
     "desc": "Aprovado o Requerimento de Urgência (Art. 155 do RICD).", "opening": "Votação do Requerimento."},
    {"id": P2, "at": "2023-04-01T15:00:00", "organ": "PLEN", "desc": "Rejeitado o Requerimento de retirada de pauta."},
    {"id": F1, "at": "2023-05-01T15:00:00", "organ": "PLEN", "desc": "Aprovado o Projeto de Lei nº 1, de 2023.",
     "opening": "  Votação em turno único. ", "presentation": "Apresentação do Projeto de Lei nº 1/2023 "},
    {"id": F2, "at": "2024-02-01T15:00:00", "organ": "PLEN",
     "desc": "Aprovada a Proposta de Emenda à Constituição nº 2, de 2024."},
    {"id": A1, "at": "2023-06-01T15:00:00", "organ": "PLEN", "desc": "Mantido o texto.",
     "opening": "Votação do DTQ 1: Destaque"},
    {"id": S1, "at": "2024-03-01T15:00:00", "organ": "PLEN", "desc": "Aprovado o Projeto de Lei nº 3, de 2023."},
    {"id": C1, "at": "2023-05-15T10:00:00", "organ": "CCJC", "desc": "Aprovado o Parecer."},
]
INDICATOR_VOTES = [
    # Party majority (others in the same party; PT = 201, 202; PL = 203 alone):
    #   P1: 201 yes, 202 yes. P2: 201 yes, 202 no. F1: 201 no, 202 yes. F2: 201 null. A1: 201 abstention, 202 null.
    # Government: P1 yes, P2 no, F1 yes, F2 no, A1 free, S1 none.
    ("201", P1, "Sim"), ("202", P1, "Sim"), ("203", P1, "Não"),
    ("201", P2, "Não"), ("202", P2, "Sim"), ("203", P2, "Obstrução"),
    ("201", F1, "Sim"), ("202", F1, "Não"), ("203", F1, "Sim"),
    ("201", F2, "Não"), ("203", F2, "Artigo 17"),
    ("201", A1, ""), ("202", A1, "Abstenção"), ("203", A1, "Sim"),
    # Committee: counted, C1 would add a government mismatch for 201 and party matches for 201 and 202.
    ("201", C1, "Não"), ("202", C1, "Não"), ("203", C1, "Sim"),
]
# participation all: 201 4/5 (A1 empty), 202 4/4 (F2 outside), 203 5/5; merit (F1 F2 A1): 201 2/3, 202 2/2, 203 3/3
# government all: 201 P1 P2 F1 F2 = 4/4, merit F1 F2 2/2; 202 P1 ok, P2 F1 miss = 1/3, merit 0/1;
#   203 P1 P2 miss, F1 ok, F2 presiding excluded = 1/3, merit 1/1
# party all: 201 P1 ok, P2 F1 miss = 1/3, merit F1 0/1; 202 P1 ok, P2 F1 miss = 1/3, merit 0/1; 203 0/0
# symbolicMerit: S1 -> 201 1, 202 0, 203 1
INDICATOR_ORIENTATIONS = [
    (P1, "GOVERNO", "Sim"), (P2, "Governo", "Não"), (F1, "Governo", "Sim"), (F1, "PT", "Sim"), (F1, "Bloco", ""),
    (F2, "Governo", "Não"), (A1, "Governo", "Liberado"), (C1, "Governo", "Sim"),
]
INDICATOR_LINKS = [
    (P1, "8001", "PL", "1", "2023", "Institui o teste."), (F1, "8001", "PL", "1", "2023", "Institui o teste."),
    (A1, "8001", "PL", "1", "2023", "Institui o teste."), (F2, "8002", "PEC", "2", "2024", "Altera a Constituição."),
    (S1, "8003", "PL", "3", "2023", "Outra lei."), (P2, "8004", "REQ", "4", "2023", "Requer a retirada."),
]
INDICATOR_PROPOSITIONS = [
    # 201: authored 5 (9001-9005), first signer 2 (9001, 9003), requirements 3 (9006-9008); 202: 1/0/0
    ("9001", "PL", "2023-03-10T10:00:00", [("201", "1", "1"), ("202", "2", "1")]),
    ("9002", "PLP", "2023-03-11T10:00:00", [("201", "2", "1")]),
    ("9003", "PEC", "2023-03-12T10:00:00", [("201", "1", "1")]),
    ("9004", "PDL", "2023-03-13T10:00:00", [("201", "3", "1")]),
    ("9005", "PRC", "2023-03-14T10:00:00", [("201", "2", "1")]),
    ("9006", "REQ", "2023-03-15T10:00:00", [("201", "1", "1")]),
    ("9007", "RIC", "2023-03-16T10:00:00", [("201", "1", "1")]),
    ("9008", "INC", "2023-03-17T10:00:00", [("201", "1", "1")]),
    ("9009", "EMC", "2023-03-18T10:00:00", [("201", "1", "1")]),
    ("9010", "PL", "2023-01-20T10:00:00", [("201", "1", "1")]),
    ("9011", "PL", "2023-04-01T10:00:00", [("201", "1", "0")]),
    ("8002", "PEC", "2024-01-10T10:00:00", []),
]


def indicators(roll_calls=None, votes=None, orientations=None) -> dict:
    return dataset(
        TRIO, roll_calls or INDICATOR_ROLL_CALLS, votes or INDICATOR_VOTES,
        orientations or INDICATOR_ORIENTATIONS, INDICATOR_LINKS, INDICATOR_PROPOSITIONS,
        lists={57: ["201", "202", "203"]},
        histories={"201": EXERCISE, "202": [*EXERCISE, ("2024-01-01T00:00", "Licença", 57)], "203": EXERCISE},
        texts={"8001": True, "8002": False},
    )


# --- classification (S3) -----------------------------------------------------------------------
K = {n: f"500-{n}" for n in range(8)} | {8: "600-8", 9: "600-9"}
CLASSIFICATION_ROLL_CALLS = [
    {"id": K[0], "at": "2023-01-31T15:00:00", "organ": "PLEN", "desc": "Aprovado o Projeto de Lei nº 9, de 2022."},
    {"id": K[1], "at": "2023-03-01T15:00:00", "organ": "PLEN",
     "desc": "Aprovado o Requerimento de Urgência (Art. 155 do RICD)."},
    {"id": K[2], "at": "2023-03-02T15:00:00", "organ": "PLEN", "desc": "Mantido o texto.",
     "opening": "Votação do DTQ 2: Destaque"},
    {"id": K[3], "at": "2023-03-03T15:00:00", "organ": "PLEN", "desc": "Aprovado o Projeto de Lei nº 5, de 2023."},
    {"id": K[4], "at": "2023-03-04T15:00:00", "organ": "PLEN",
     "desc": "Alteração do Regime de Tramitação desta proposição."},
    {"id": K[5], "at": "2023-03-05T15:00:00", "organ": "PLEN", "desc": "Votos: Sim: 300; Não: 10.",
     "opening": "Votação secreta em turno único.", "totals": ("300", "10", "1")},
    {"id": K[6], "at": "2023-03-06T15:00:00", "organ": "PLEN", "desc": "Aprovadas."},
    {"id": K[7], "at": "2023-03-07T15:00:00", "organ": "PLEN",
     "desc": "Aprovada a Proposta de Emenda à Constituição nº 7, de 2023.", "opening": "Votação em segundo turno.",
     "totals": ("12", "5", "2")},
    {"id": K[8], "at": "2023-03-08T10:00:00", "organ": "CCJC", "desc": "Aprovado o Parecer."},
    {"id": K[9], "at": "2023-03-09T10:00:00", "organ": "CCJC", "desc": "Aprovado o Parecer."},
]
CLASSIFICATION_VOTES = [
    ("201", K[0], "Sim"),
    ("201", K[1], "Sim"), ("202", K[1], "Não"),
    ("201", K[2], "Não"), ("202", K[2], "Sim"),
    ("201", K[7], ""), ("202", K[7], ""), ("203", K[7], ""),
    ("201", K[9], "Sim"),
]


def classification(extra_roll_calls=(), lists=None) -> dict:
    """`lists` replaces the per-legislature deputy lists (the 58th list is empty before it starts)."""
    return dataset(
        TRIO, [*CLASSIFICATION_ROLL_CALLS, *extra_roll_calls], CLASSIFICATION_VOTES,
        lists=lists or {57: ["201", "202", "203"]}, histories={dep: EXERCISE for dep in TRIO},
    )


# --- legislatures (S2), clock 2027-03-01 -------------------------------------------------------
L1, L2 = "700-1", "700-2"
LEGISLATURE_DEPUTIES = {"301": ("Rita Alves", "PT", "SP"), "302": ("Saulo Melo", "PDT", "CE"),
                        "303": ("Tânia Reis", "PL", "PR")}


def legislatures() -> dict:
    return dataset(
        LEGISLATURE_DEPUTIES,
        [{"id": L1, "at": "2027-01-20T15:00:00", "organ": "PLEN", "desc": "Aprovado o Projeto de Lei nº 7, de 2027."},
         {"id": L2, "at": "2027-02-03T15:00:00", "organ": "PLEN", "desc": "Aprovado o Projeto de Lei nº 8, de 2027."}],
        [("301", L1, "Sim"),
         ("301", L2, "Não", {"deputado_nome": "Rita Alves Lima", "deputado_siglaPartido": "PSB",
                             "deputado_urlFoto": "https://www.camara.leg.br/internet/deputado/bandep/301-58.jpg"}),
         ("303", L2, "Sim")],
        propositions=[("9101", "PL", "2023-05-01T10:00:00", [("301", "1", "1")]),
                      ("9102", "PL", "2027-02-10T10:00:00", [("301", "1", "1")])],
        lists={57: ["301", "302"], 58: ["301"]},
        histories={
            "301": [("2019-02-01T00:00", "Exercício", 56), ("2023-02-01T10:00", "Exercício", 57),
                    ("2027-02-01T10:00", "Exercício", 58)],
            "302": [("2023-02-01T00:00", "Suplência", 57)],
            "303": [("2027-02-01T10:00", "Exercício", 58)],
        },
    )


def pin(monkeypatch, moment: datetime) -> None:
    monkeypatch.setattr(cli, "now", lambda: moment)


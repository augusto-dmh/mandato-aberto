"""Streams the source CSVs through a per-file column allowlist.

A column outside the allowlist is never bound to a key, so a value such as the CPF that
`deputados.csv` carries cannot leave this module (AD-003).
"""

import csv
import json
import sys
from collections.abc import Iterator
from pathlib import Path

csv.field_size_limit(sys.maxsize)

ALLOWLIST = {
    "votacoes": [
        "id",
        "data",
        "dataHoraRegistro",
        "siglaOrgao",
        "aprovacao",
        "votosSim",
        "votosNao",
        "votosOutros",
        "descricao",
        "ultimaAberturaVotacao_descricao",
        "ultimaApresentacaoProposicao_descricao",
    ],
    "votacoesVotos": [
        "idVotacao",
        "dataHoraVoto",
        "voto",
        "deputado_id",
        "deputado_nome",
        "deputado_siglaPartido",
        "deputado_siglaUf",
        "deputado_idLegislatura",
        "deputado_urlFoto",
    ],
    "votacoesOrientacoes": ["idVotacao", "siglaBancada", "orientacao"],
    "votacoesProposicoes": [
        "idVotacao",
        "proposicao_id",
        "proposicao_titulo",
        "proposicao_ementa",
        "proposicao_siglaTipo",
        "proposicao_numero",
        "proposicao_ano",
    ],
    "proposicoes": [
        "id",
        "siglaTipo",
        "numero",
        "ano",
        "ementa",
        "dataApresentacao",
        "ultimoStatus_descricaoSituacao",
    ],
    "proposicoesAutores": ["idProposicao", "idDeputadoAutor", "ordemAssinatura", "proponente"],
    "deputados": ["uri", "nomeCivil", "dataNascimento"],
    # TSE `consulta_cand` layout; the only place that names its columns.
    "tse": [
        "NM_CANDIDATO",
        "DT_NASCIMENTO",
        "SG_UF",
        "DS_CARGO",
        "SG_PARTIDO",
        "NR_CANDIDATO",
        "DS_SITUACAO_CANDIDATURA",
    ],
}

ENCODING = {"tse": "latin-1"}


class SourceLayoutError(Exception):
    """A source file lacks a column the allowlist expects."""


def read(kind: str, path: Path) -> Iterator[dict[str, str]]:
    allowed = ALLOWLIST[kind]
    with open(path, encoding=ENCODING.get(kind, "utf-8-sig"), newline="") as f:
        rows = csv.reader(f, delimiter=";")
        header = next(rows, [])
        missing = [c for c in allowed if c not in header]
        if missing:
            raise SourceLayoutError(f"{path.name}: missing columns {', '.join(missing)}")
        index = [(c, header.index(c)) for c in allowed]
        for row in rows:
            if row:
                yield {c: row[i] for c, i in index}


# --- Senate JSON (doors 1, 5, 6). Each kind is a nested map from a kept key to `None` (keep the
# value) or to the map of the object or list of objects under it. A key outside the map is never
# copied, so `NomeCompletoParlamentar`, `EmailParlamentar`, `sexoParlamentar` and the like cannot
# leave this module.

_PERIOD = {"NumeroLegislatura": None, "DataInicio": None, "DataFim": None}
_PARLAMENTAR = {
    "IdentificacaoParlamentar": {
        "CodigoParlamentar": None, "NomeParlamentar": None, "SiglaPartidoParlamentar": None, "UfParlamentar": None,
    },
    "Mandatos": {"Mandato": {
        "UfParlamentar": None, "PrimeiraLegislaturaDoMandato": _PERIOD, "SegundaLegislaturaDoMandato": _PERIOD,
        "DescricaoParticipacao": None, "Exercicios": {"Exercicio": {"DataInicio": None, "DataFim": None}},
    }},
}
_PROCESSO = {
    "id": None, "codigoMateria": None, "identificacao": None, "tipoDocumento": None, "ementa": None,
    "dataApresentacao": None, "situacaoAtual": None, "dataSituacaoAtual": None, "tramitando": None, "autoria": None,
    "urlDocumento": None, "casaIdentificadora": None,
}
SENADO_ALLOWLIST = {
    "senado-votacao": {
        "codigoSessaoVotacao": None, "sequencialVotacao": None, "codigoSessao": None, "dataSessao": None,
        "descricaoVotacao": None, "idProcesso": None, "codigoMateria": None, "sigla": None, "numero": None,
        "ano": None, "ementa": None, "resultadoVotacao": None, "votacaoSecreta": None, "totalVotosSim": None,
        "totalVotosNao": None, "totalVotosAbstencao": None,
        "votos": {
            "codigoParlamentar": None, "nomeParlamentar": None, "siglaPartidoParlamentar": None,
            "siglaUFParlamentar": None, "siglaVotoParlamentar": None,
        },
    },
    "senado-orientacao": {"votacoes": {"sequencialVotacao": None, "orientacoesLideranca": {"partido": None, "voto": None}}},
    "senado-legislatura": {"ListaParlamentarLegislatura": {"Parlamentares": {"Parlamentar": _PARLAMENTAR}}},
    "senado-atual": {"ListaParlamentarEmExercicio": {"Parlamentares": {"Parlamentar": _PARLAMENTAR}}},
    "senado-processos": _PROCESSO,
    "senado-processo": {"id": None, "autoriaIniciativa": {"ordem": None, "codigoParlamentar": None}},
}
# The path each kind must hold, read as a list (AC 6); `()` is the document itself.
SENADO_ENVELOPE = {
    "senado-votacao": (),
    "senado-orientacao": ("votacoes",),
    "senado-legislatura": ("ListaParlamentarLegislatura", "Parlamentares", "Parlamentar"),
    "senado-atual": ("ListaParlamentarEmExercicio", "Parlamentares", "Parlamentar"),
    "senado-processos": (),
    "senado-processo": ("autoriaIniciativa",),
}


def as_list(value) -> list:
    """The Senate's PascalCase services send one item as an object and none as a missing key (AC 10)."""
    if value is None:
        return []
    return value if isinstance(value, list) else [value]


def project(doc, spec):
    """`doc` reduced to the keys of `spec`; a nested spec applies to an object or to each object of a list."""
    if spec is None:
        return doc
    if isinstance(doc, list):
        return [project(item, spec) for item in doc]
    if not isinstance(doc, dict):
        return doc
    return {key: project(doc[key], sub) for key, sub in spec.items() if key in doc}


def read_senado(kind: str, path: Path, name: str | None = None):
    """The allowlisted content of a cached Senate response, or `SourceLayoutError` naming the file (`name`)."""
    name = name or path.name
    try:
        doc = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as e:
        raise SourceLayoutError(f"{name}: not a JSON document ({e})") from None
    node = doc
    for key in SENADO_ENVELOPE[kind]:
        node = node.get(key) if isinstance(node, dict) else None
    if kind in ("senado-votacao", "senado-processos") and not isinstance(node, list):
        raise SourceLayoutError(f"{name}: expected a JSON list")
    if node is None or (SENADO_ENVELOPE[kind] and not isinstance(node, (list, dict))):
        raise SourceLayoutError(f"{name}: missing {'.'.join(SENADO_ENVELOPE[kind])}")
    return project(doc, SENADO_ALLOWLIST[kind])

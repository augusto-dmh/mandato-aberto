"""Streams the source CSVs through a per-file column allowlist.

A column outside the allowlist is never bound to a key, so a value such as the CPF that
`deputados.csv` carries cannot leave this module (AD-003).
"""

import csv
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

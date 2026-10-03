"""`mandato-etl build` and `mandato-etl validate`.

Exit codes: 0 success, 1 usage or invalid output, 2 a source could not be downloaded.
"""

import argparse
import json
import sys
from collections import defaultdict
from datetime import UTC, datetime, timedelta, timezone
from pathlib import Path

from mandato_etl import classify, compute, contract_v3, presidency, publish, readers, schema
from mandato_etl.sources import camara, congresso, senado, tse

ROOT = Path(__file__).resolve().parents[3]
RAW_DIR = ROOT / "data" / "raw"
OUT_DIR = ROOT / "data" / "out"
V3_DIR = ROOT / "data" / "v3"
V4_DIR = ROOT / "data" / "v4"
HOUSES = ("camara", "senado")
PRESIDENCY = "presidencia"
FIRST_YEAR = 2023
SCHEMA_VERSION = 2
BRASILIA = timezone(timedelta(hours=-3))


def now() -> datetime:
    return datetime.now(UTC)


class _Parser(argparse.ArgumentParser):
    def error(self, message):
        self.print_usage(sys.stderr)
        self.exit(1, f"{self.prog}: error: {message}\n")


def _parser(current_year: int) -> tuple[_Parser, _Parser]:
    parser = _Parser(prog="mandato-etl", description=__doc__.splitlines()[0])
    commands = parser.add_subparsers(dest="command", required=True, parser_class=_Parser)
    build = commands.add_parser("build", help="download the sources and write the JSON contract")
    build.add_argument(
        "--years", type=int, nargs="+", metavar="YEAR",
        help=f"years of bulk files to read, {FIRST_YEAR}-{current_year} (default: all)",
    )
    build.add_argument("--refresh", action="store_true", help="download files already in the cache again")
    build.add_argument("--tse-csv", type=Path, help="TSE consulta_cand CSV for the 2026 candidacy badge")
    build.add_argument(
        "--export-candidacy", type=Path, metavar="JSON",
        help="with --tse-csv: also write the CPF-free candidacy file that --candidacy-json reads",
    )
    build.add_argument(
        "--candidacy-json", type=Path, metavar="JSON",
        help="candidacy file written by --export-candidacy, read in place of --tse-csv",
    )
    build.add_argument(
        "--contract", type=int, choices=(2, 3, 4), default=2,
        help="contract version to write; 3 and 4 take no candidacy flag (default: 2)",
    )
    build.add_argument(
        "--house", choices=(*HOUSES, PRESIDENCY), default="camara",
        help="with --contract 3 or 4: the house; presidencia only with --contract 4 (default: camara)",
    )
    build.add_argument(
        "--out", type=Path,
        help="output directory (default: data/out, or data/v<contract>/<house> with --contract 3 or 4); "
             "the presidencia build reads the house directories beside it",
    )
    build.add_argument("--quiet", action="store_true", help="print errors only")
    validate = commands.add_parser("validate", help="validate a directory against etl/schema, by its schema_version")
    validate.add_argument("dir", type=Path, nargs="?", default=OUT_DIR)
    return parser, build


def main(argv: list[str] | None = None) -> int:
    started = now()
    parser, build_parser = _parser(started.year)
    try:
        args = parser.parse_args(argv)
    except SystemExit as e:
        return e.code
    if args.command == "validate":
        error = schema.validate_dir(args.dir)
        if error:
            print(f"invalid: {error}", file=sys.stderr)
            return 1
        return 0

    years = args.years or list(range(FIRST_YEAR, started.year + 1))
    outside = [y for y in years if not FIRST_YEAR <= y <= started.year]
    if outside:
        build_parser.print_usage(sys.stderr)
        print(f"{build_parser.prog}: error: --years must be within {FIRST_YEAR}-{started.year}", file=sys.stderr)
        return 1
    if args.house == PRESIDENCY and args.contract != 4:
        build_parser.print_usage(sys.stderr)
        print(f"{build_parser.prog}: error: --house {args.house} needs --contract 4", file=sys.stderr)
        return 1
    if args.house != "camara" and args.contract == 2:
        build_parser.print_usage(sys.stderr)
        print(f"{build_parser.prog}: error: --house {args.house} needs --contract 3", file=sys.stderr)
        return 1
    if args.contract in (3, 4):
        given = [flag for flag, value in (("--tse-csv", args.tse_csv), ("--candidacy-json", args.candidacy_json),
                                          ("--export-candidacy", args.export_candidacy)) if value is not None]
        if given:
            build_parser.print_usage(sys.stderr)
            print(f"{build_parser.prog}: error: --contract {args.contract} takes no {given[0]}", file=sys.stderr)
            return 1
        log = (lambda msg: None) if args.quiet else (lambda msg: print(msg, file=sys.stderr))
        out = args.out if args.out is not None else (V3_DIR if args.contract == 3 else V4_DIR) / args.house
        try:
            if args.house == PRESIDENCY:
                build_presidency(sorted(set(years)), args.refresh, out, started, log)
            else:
                build_v3(sorted(set(years)), args.refresh, args.house, out, started, log, args.contract)
        except camara.DownloadError as e:
            print(f"error: could not download {e.url} ({e.args[0].split(': ', 1)[-1]})", file=sys.stderr)
            return 2
        except (schema.SchemaError, readers.SourceLayoutError, contract_v3.ContractError,
                presidency.PresidencyError) as e:
            for line in str(e).splitlines():
                print(f"error: {line}", file=sys.stderr)
            return 1
        return 0
    if args.out is None:
        args.out = OUT_DIR
    if args.tse_csv is not None and args.candidacy_json is not None:
        build_parser.print_usage(sys.stderr)
        print(f"{build_parser.prog}: error: --tse-csv and --candidacy-json are mutually exclusive", file=sys.stderr)
        return 1
    if args.export_candidacy is not None and args.tse_csv is None:
        build_parser.print_usage(sys.stderr)
        print(f"{build_parser.prog}: error: --export-candidacy needs --tse-csv", file=sys.stderr)
        return 1
    if args.export_candidacy is not None and not args.tse_csv.is_file():
        print(f"error: --export-candidacy needs the TSE file, and {args.tse_csv} does not exist", file=sys.stderr)
        return 1
    candidacy = None
    if args.candidacy_json is not None:
        try:
            candidacy = (args.candidacy_json.name, *tse.read_export(args.candidacy_json))
        except tse.CandidacyFileError as e:
            print(f"error: invalid candidacy file {e}", file=sys.stderr)
            return 1
    log = (lambda msg: None) if args.quiet else (lambda msg: print(msg, file=sys.stderr))
    try:
        build(sorted(set(years)), args.refresh, args.tse_csv, args.out, started, log, candidacy, args.export_candidacy)
    except camara.DownloadError as e:
        print(f"error: could not download {e.url} ({e.args[0].split(': ', 1)[-1]})", file=sys.stderr)
        return 2
    except (schema.SchemaError, readers.SourceLayoutError) as e:
        print(f"error: {e}", file=sys.stderr)
        return 1
    return 0


def build(
    years: list[int], refresh: bool, tse_csv: Path | None, out: Path, started: datetime, log,
    candidacy: tuple[str, dict[str, dict], list[int]] | None = None, export: Path | None = None,
) -> None:
    """`candidacy` is `(file name, matched, ambiguous)` from `--candidacy-json`, used in place of `tse_csv`."""
    log(f"sources: {years[0]}-{years[-1]}")
    sources = camara.sync(RAW_DIR, years, refresh, started, log)

    def read(kind):
        for year in years:
            yield from readers.read(kind, RAW_DIR / f"{kind}-{year}.csv")

    log("reading roll calls, votes and propositions")
    data = compute.load(read)
    log(f"fetching the in-exercise list and {len(data.profiles)} histories")
    in_exercise = camara.deputies_in_exercise()
    histories = camara.histories(sorted(data.profiles, key=int), RAW_DIR, refresh)

    candidacies, ambiguous = {}, []
    tse_file = tse_csv if tse_csv is not None and tse_csv.is_file() else None
    candidacy_file = tse_file.name if tse_file else None
    if candidacy is not None:
        candidacy_file, matched, ambiguous = candidacy
        candidacies = {dep: found for dep, found in matched.items() if dep in data.profiles}
        log(f"candidacy 2026 from {candidacy_file}: {len(candidacies)} matched, {len(ambiguous)} ambiguous")
    elif tse_file:
        civil = {
            r["uri"].rstrip("/").rsplit("/", 1)[-1]: (r["nomeCivil"], r["dataNascimento"])
            for r in readers.read("deputados", RAW_DIR / "deputados.csv")
        }
        keys = {dep: (*civil[dep], p["deputado_siglaUf"]) for dep, p in data.profiles.items() if dep in civil}
        candidacies, ambiguous = tse.match(keys, readers.read("tse", tse_file))
        log(f"candidacy 2026: {len(candidacies)} matched, {len(ambiguous)} ambiguous")
    else:
        print("warning: no TSE file; candidacy2026 is null for every deputy", file=sys.stderr)

    local_now = started.astimezone(BRASILIA).strftime("%Y-%m-%dT%H:%M:%S")
    records = compute.assemble(data, histories, in_exercise, candidacies, local_now)
    meta = {
        "schema_version": SCHEMA_VERSION,
        "generatedAt": started.astimezone(UTC).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "years": years,
        "counts": {
            "deputies": len(records["deputies"]),
            "rollCalls": len(records["roll_calls"]),
            "propositions": records["propositions"],
        },
        "sources": sources,
        "candidacy": {
            "file": candidacy_file,
            "matched": len(candidacies),
            "ambiguous": [int(dep) for dep in ambiguous],
        },
    }
    files = {"meta.json": meta, "deputies.json": records["deputies"], "roll-calls.json": records["roll_calls"]}
    files |= {f"deputies/{dep}.json": doc for dep, doc in records["deputy_docs"].items()}
    files |= {f"roll-calls/{rc}.json": doc for rc, doc in records["roll_call_docs"].items()}
    log(f"writing {len(files)} files to {out}")
    publish.write(out, files)
    if export is not None:
        tse.export(export, tse_file, candidacies, ambiguous)
        log(f"wrote the candidacy file {export}")


def build_v3(years: list[int], refresh: bool, house: str, out: Path, started: datetime, log, version: int = 3) -> None:
    """Writes `data/v3/<house>/` (contract v3), or the same files at `schema_version` 4 for contract v4.

    `data/out/` is not touched.
    """
    from mandato_etl import full_texts

    if house == "senado":
        build_senado(years, refresh, out, started, log, version)
        return

    log(f"sources: {years[0]}-{years[-1]}")
    sources = camara.sync(RAW_DIR, years, refresh, started, log)

    def read(kind):
        for year in years:
            yield from readers.read(kind, RAW_DIR / f"{kind}-{year}.csv")

    log("reading roll calls, votes and propositions")
    data = contract_v3.load(read)
    local_now = started.astimezone(BRASILIA).strftime("%Y-%m-%dT%H:%M:%S")
    legislatures = contract_v3.started(local_now[:10])
    lists, list_sources = camara.legislature_members(RAW_DIR, legislatures, refresh, started)
    members = contract_v3.member_ids(data, lists)
    log(f"fetching {len(members)} histories")
    histories, history_sources = camara.histories_v3(RAW_DIR, sorted(members, key=int), refresh, started)
    ruleset = classify.load_rules(house)
    result = contract_v3.assemble(data, lists, histories, ruleset, local_now)
    log(f"fetching the inteiro teor of {len(result['targets'])} propositions")
    found, text_sources = camara.proposition_documents(RAW_DIR, result["targets"], refresh, started)
    generated = started.astimezone(UTC).strftime("%Y-%m-%dT%H:%M:%SZ")
    meta = {
        "schema_version": version,
        "house": house,
        "generatedAt": generated,
        "legislatures": [
            {"id": n, "start": contract_v3.LEGISLATURES[n][0], "end": contract_v3.LEGISLATURES[n][1],
             "sourceUrl": contract_v3.LEGISLATURE_URL.format(id=n)}
            for n in legislatures
        ],
        "coverage": result["coverage"],
        "classification": {"version": ruleset["version"]},
        "sources": sorted([*sources, *list_sources, *history_sources, *text_sources], key=lambda e: e["file"]),
    }
    files = {"meta.json": meta, **result["files"], **full_texts.documents(house, found, generated)}
    log(f"writing {len(files)} files to {out}")
    publish.write(out, files, version=version)
    for row in result["coverage"]:
        counts = row["rollCalls"]
        log(f"{house} {row['legislature']}: {sum(n or 0 for n in counts.values())} roll calls "
            f"({counts['nominal']} nominal, {counts['secret']} secret, {counts['symbolic']} symbolic), "
            f"{row['unclassified']} unclassified")


def build_senado(years: list[int], refresh: bool, out: Path, started: datetime, log, version: int = 3) -> None:
    """Writes `data/v3/senado/` from the Senate open data; nothing else under `data/` changes."""
    warn = lambda msg: print(msg, file=sys.stderr)  # noqa: E731
    local_now = started.astimezone(BRASILIA).strftime("%Y-%m-%dT%H:%M:%S")
    dates = {n: contract_v3.LEGISLATURES[n] for n in contract_v3.started(local_now[:10])}
    log(f"sources: senado {years[0]}-{years[-1]}, legislatures {', '.join(map(str, dates))}")
    files, sources = senado.lists(RAW_DIR, dates, years, refresh, started, warn)

    records = [
        r for relative in files["votacao"] for r in senado.read(RAW_DIR, "senado-votacao", relative)
        if any(start <= (r.get("dataSessao") or "") <= end for start, end in dates.values())
    ]
    records = senado.dedupe_twins(records)
    orientations = defaultdict(list)
    for relative in files["orientacao"]:
        for item in senado.read(RAW_DIR, "senado-orientacao", relative)["votacoes"]:
            for o in item.get("orientacoesLideranca") or []:
                if o.get("voto") is not None:
                    orientations[item["sequencialVotacao"]].append((o["partido"], o["voto"]))
    lists = {
        n: readers.as_list(senado.read(RAW_DIR, "senado-legislatura", relative)
                           ["ListaParlamentarLegislatura"]["Parlamentares"]["Parlamentar"])
        for n, relative in files["legislatura"].items()
    }
    senado.read(RAW_DIR, "senado-atual", files["atual"])

    mandates = contract_v3.senate_mandates(lists, records)
    starts = {code: contract_v3.LEGISLATURES[legislatures[0]][0] for code, legislatures in mandates.items()}
    log(f"fetching the authorship of {len(starts)} senators")
    processes, process_sources = senado.authorship(RAW_DIR, starts, refresh, started, warn)
    multi = contract_v3.senate_multi_author(processes, mandates)
    log(f"fetching the authors of {len(multi)} processes with more than one")
    details, detail_sources = senado.details(RAW_DIR, multi, refresh, started, warn)

    ruleset = classify.load_rules("senado")
    result = contract_v3.assemble_senado(records, orientations, lists, processes, details, ruleset, local_now)
    for legislature, count in sorted(result["mismatches"].items()):
        warn(f"warning: senado {legislature}: {count} vote records disagree with the exercise periods "
             "(a record outside every period, or none in a roll call inside one)")
    meta = {
        "schema_version": version,
        "house": "senado",
        "generatedAt": started.astimezone(UTC).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "legislatures": [
            {"id": n, "start": start, "end": end,
             "sourceUrl": contract_v3.SENATE_LEGISLATURE_URL.format(start=start.replace("-", ""))}
            for n, (start, end) in dates.items()
        ],
        "coverage": result["coverage"],
        "classification": {"version": ruleset["version"]},
        "sources": sorted([*sources, *process_sources, *detail_sources], key=lambda e: e["file"]),
    }
    files_out = {"meta.json": meta, **result["files"]}
    log(f"writing {len(files_out)} files to {out}")
    publish.write(out, files_out, version=version)
    for row in result["coverage"]:
        counts = row["rollCalls"]
        log(f"senado {row['legislature']}: {counts['nominal'] + counts['secret']} roll calls "
            f"({counts['nominal']} nominal, {counts['secret']} secret, 0 symbolic), {row['unclassified']} unclassified")


def _house_members(out: Path) -> dict[str, list[dict]]:
    """`members.json` of the v4 house directories beside `out`, which must exist and validate (door 6, AC 38)."""
    members = {}
    for house in HOUSES:
        directory = out.parent / house
        if not (directory / "meta.json").is_file():
            raise presidency.PresidencyError(
                f"{house}: no v4 house directory at {directory} (run build --contract 4 --house {house} first)")
        if schema.version_of(directory) != 4 or schema.scope_of(directory) is not None:
            raise presidency.PresidencyError(f"{house}: {directory} is not a v4 house directory")
        error = schema.validate_dir(directory)
        if error:
            raise presidency.PresidencyError(f"{house}/{error}")
        members[house] = json.loads((directory / "members.json").read_text())
    return members


def build_presidency(years: list[int], refresh: bool, out: Path, started: datetime, log) -> None:
    """Writes `data/v4/presidencia/` from the Congress open data, the Câmara yearly files and the v4 house members."""
    members = _house_members(out)
    local_day = started.astimezone(BRASILIA).strftime("%Y-%m-%d")
    log(f"sources: presidencia {years[0]}-{years[-1]}")
    camara_sources = camara.sync(RAW_DIR, years, refresh, started, log)
    read_kinds = {f"{kind}-{year}.csv" for kind in ("proposicoes", "proposicoesAutores") for year in years}
    sources = [e for e in camara_sources if e["file"] in read_kinds]
    propositions = [r for y in years for r in readers.read("proposicoes", RAW_DIR / f"proposicoes-{y}.csv")]
    authors = [r for y in years for r in readers.read("proposicoesAutores", RAW_DIR / f"proposicoesAutores-{y}.csv")]

    lists = [item for year in years for item in (congresso.mp_list(year), congresso.veto_list(year))]
    sources += congresso.fetch(RAW_DIR, lists, set(), started)
    mp_records, veto_entries = [], []
    for year in years:
        found = congresso.read(RAW_DIR, congresso.mp_list(year)[0])
        if not isinstance(found, list):
            raise readers.SourceLayoutError(f"{congresso.mp_list(year)[0]}: expected a JSON list")
        mp_records += found
        doc = congresso.read(RAW_DIR, congresso.veto_list(year)[0])
        try:
            vetos = doc["ListaVetosAnoCN"].get("Vetos") or {}
        except (KeyError, AttributeError, TypeError):
            raise readers.SourceLayoutError(f"{congresso.veto_list(year)[0]}: missing ListaVetosAnoCN") from None
        veto_entries += readers.as_list(vetos.get("Veto"))

    results = [congresso.veto_result(e["Materia"]["Codigo"]) for e in veto_entries]
    log(f"fetching {len(results)} veto results")
    sources += congresso.fetch(RAW_DIR, results, set(), started)
    vetoes = [(e, congresso.read(RAW_DIR, relative)) for e, (relative, _) in zip(veto_entries, results)]
    voted, reuse = [], set()
    for _, result in vetoes:
        for d in readers.as_list((result["ResultadoVetoMateriaCN"]["Veto"].get("Dispositivos") or {})
                                 .get("Dispositivo")):
            if d.get("PossuiVotos") == "Sim" and d.get("Codigo"):
                item = congresso.device_votes(d["Codigo"])
                voted.append((d["Codigo"], item))
                if not refresh and d.get("Situacao") in ("Mantido", "Rejeitado"):
                    reuse.add(item[0])
    log(f"fetching the votes of {len(voted)} veto devices ({len(reuse)} decided, from the cache when present)")
    sources += congresso.fetch(RAW_DIR, [item for _, item in voted], reuse, started)
    device_docs = {codigo: congresso.read(RAW_DIR, item[0]) for codigo, item in voted}

    bills = [b for b in presidency.executive_bills(propositions, authors)
             if b["dataApresentacao"][:10] >= presidency.TERMS[0]["start"]]
    processes = {b["id"]: congresso.bill_process(b["siglaTipo"], int(b["numero"]), int(b["ano"])) for b in bills}
    log(f"fetching the Senate process of {len(processes)} Executive bills")
    sources += congresso.fetch(RAW_DIR, list(processes.values()), set(), started)
    bill_processes = {}
    for bill, (relative, _) in processes.items():
        found = congresso.read(RAW_DIR, relative)
        if not isinstance(found, list):
            raise readers.SourceLayoutError(f"{relative}: expected a JSON list")
        bill_processes[bill] = found

    result = presidency.assemble(local_day, mp_records, vetoes, device_docs,
                                 presidency.executive_bills(propositions, authors), bill_processes, propositions,
                                 members, presidency.load_aliases())
    meta = {
        "schema_version": 4,
        "scope": PRESIDENCY,
        "generatedAt": started.astimezone(UTC).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "terms": result["terms"],
        "coverage": result["coverage"],
        "statusRules": {"version": presidency.STATUS_RULES_VERSION},
        "sources": sorted({e["file"]: e for e in sources}.values(), key=lambda e: e["file"]),
    }
    files = {"meta.json": meta, **result["files"]}
    log(f"writing {len(files)} files to {out}")
    publish.write(out, files, version=4, scope=schema.PRESIDENCY)
    for term, n in result["summary"].items():
        log(f"presidencia {term}: {n['mps']} MPs, {n['vetoes']} vetoes ({n['devices']} devices), {n['bills']} bills, "
            f"{n['joint']} joint roll calls, 0 unmatched votes")

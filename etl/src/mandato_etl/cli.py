"""`mandato-etl build` and `mandato-etl validate`.

Exit codes: 0 success, 1 usage or invalid output, 2 a source could not be downloaded.
"""

import argparse
import sys
from datetime import UTC, datetime, timedelta, timezone
from pathlib import Path

from mandato_etl import compute, publish, readers, schema
from mandato_etl.sources import camara, tse

ROOT = Path(__file__).resolve().parents[3]
RAW_DIR = ROOT / "data" / "raw"
OUT_DIR = ROOT / "data" / "out"
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
    build.add_argument("--out", type=Path, default=OUT_DIR, help="output directory (default: data/out)")
    build.add_argument("--quiet", action="store_true", help="print errors only")
    validate = commands.add_parser("validate", help="validate a directory against etl/schema")
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

"""S2 - deputies and exercise periods (C8-C12)."""


from conftest import CPF_IN_DEPUTADOS, COLUMNS, load, render

from mandato_etl import compute, readers


def test_deputy_set_is_every_legislature_57_voter(built):
    assert sorted(d["id"] for d in load(built.out, "deputies.json")) == [101, 102, 103]
    assert not (built.out / "deputies" / "104.json").exists()


def test_readers_keep_only_allowlisted_columns(tmp_path):
    assert all("cpf" not in [c.casefold() for c in cols] for cols in readers.ALLOWLIST.values())
    for kind in readers.ALLOWLIST:
        path = tmp_path / f"{kind}.csv"
        encoding = readers.ENCODING.get(kind, "utf-8-sig")
        path.write_bytes(render(kind, [{c: f"v-{c}" for c in COLUMNS[kind]}], encoding=encoding))
        rows = list(readers.read(kind, path))
        assert len(rows) == 1
        assert list(rows[0]) == readers.ALLOWLIST[kind]
        assert rows[0] == {c: f"v-{c}" for c in readers.ALLOWLIST[kind]}


def test_cpf_never_reaches_output(built):
    raw = (built.raw / "deputados.csv").read_text(encoding="utf-8-sig")
    assert CPF_IN_DEPUTADOS in raw  # the source does carry it
    for path in built.out.rglob("*.json"):
        text = path.read_text()
        assert '"cpf"' not in text.casefold(), path
        assert CPF_IN_DEPUTADOS not in text, path


def test_exercise_periods_are_half_open_and_close_at_build_time(built):
    history = [
        {"dataHora": "2022-12-01T10:00", "situacao": "Exercício"},
        {"dataHora": "2023-02-01T12:05", "situacao": "Exercício"},
        {"dataHora": "2024-03-01T00:00", "situacao": "Licença"},
        {"dataHora": "2025-01-10T10:00", "situacao": "Exercício"},
    ]
    expected = [
        {"start": "2023-02-01T12:05:00", "end": "2024-03-01T00:00:00"},
        {"start": "2025-01-10T10:00:00", "end": "2026-09-27T09:00:00"},
    ]
    periods = compute.exercise_periods(history, "2026-09-27T09:00:00")
    assert periods == expected
    assert compute.in_periods("2023-02-01T12:05:00", periods) is True
    assert compute.in_periods("2024-03-01T00:00:00", periods) is False
    assert compute.in_periods("2024-02-29T23:59:59", periods) is True
    # the same history, fetched through the API, lands in the deputy file
    assert load(built.out, "deputies/102.json")["exercisePeriods"] == expected


def test_in_exercise_follows_api_list(built):
    flags = {d["id"]: d["inExercise"] for d in load(built.out, "deputies.json")}
    assert flags == {101: True, 102: True, 103: False}


def _vote(at, party, uf, name, photo):
    return {
        "idVotacao": "1-1", "dataHoraVoto": at, "voto": "Sim", "deputado_id": "7", "deputado_nome": name,
        "deputado_siglaPartido": party, "deputado_siglaUf": uf, "deputado_idLegislatura": "57",
        "deputado_urlFoto": photo,
    }


def test_profile_fields_come_from_latest_vote():
    votes = [
        _vote("2025-05-01T10:00:00", "PL", "RJ", "Novo Nome", "https://x/new.jpg"),
        _vote("2023-03-01T10:00:00", "PSD", "SP", "Nome Antigo", "https://x/old.jpg"),
    ]
    sources = {
        "votacoes": [{"id": "1-1", "data": "2023-03-01", "dataHoraRegistro": "2023-03-01T10:00:00",
                      "siglaOrgao": "PLEN", "aprovacao": "1", "descricao": "d"}],
        "votacoesVotos": votes,
    }
    data = compute.load(lambda kind: sources.get(kind, []))
    record = compute.assemble(data, {}, set(), {}, "2026-09-27T09:00:00")["deputies"][0]
    assert (record["party"], record["uf"], record["name"], record["photoUrl"]) == (
        "PL", "RJ", "Novo Nome", "https://x/new.jpg"
    )

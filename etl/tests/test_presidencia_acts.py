"""etl-presidencia S3 - every act with its status, term and rule (C14-C28, C58)."""

import json
import unicodedata

import presidencia_data as pd
import pytest

from mandato_etl import cli, presidency, readers

R_IDS = ["mpv-1154-2023", "mpv-1155-2023", "mpv-1169-2023", "mpv-1177-2023", "mpv-1181-2023", "mpv-1350-2026",
         "vet-17-2023", "vet-49-2023", "vet-3-2025", "vet-29-2025", "vet-3-2026", "pl-3626-2023", "plp-93-2023",
         "pl-6233-2023", "pl-1084-2023", "pl-9101-2023", "pl-9102-2025", "pec-9103-2026"]


@pytest.fixture
def r(con):
    data = pd.recorded()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    return con


@pytest.fixture
def h(con):
    data = pd.hand()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    return con


def test_one_act_per_mp_veto_and_executive_bill(r):
    ids = [a["id"] for a in pd.load(r, "acts.json")]
    assert sorted(ids) == sorted(R_IDS)
    assert len(ids) == 18
    assert not any(i.startswith(("msc-", "pln-")) or i in ("pl-1-2023", "pl-9203-2023", "pl-9204-2023") for i in ids)


def test_kind_type_and_veto_scope(r):
    acts = {a["id"]: a for a in pd.load(r, "acts.json")}
    expected = {"mpv": ("provisionalMeasure", "MPV"), "vet": ("veto", "VET"), "pl": ("bill", "PL"),
                "plp": ("bill", "PLP"), "pec": ("bill", "PEC")}
    for id, a in acts.items():
        prefix, number, year = id.split("-")
        assert (a["kind"], a["type"]) == expected[prefix]
        assert (a["number"], a["year"]) == (int(number), int(year))
        if a["kind"] == "veto":
            assert a["vetoScope"] == ("total" if id == "vet-3-2026" else "partial")
        else:
            assert a["vetoScope"] is None
            assert a["devices"] == []
    assert acts["plp-93-2023"]["type"] == "PLP" and acts["pec-9103-2026"]["type"] == "PEC"


def test_before_first_term_is_excluded(r):
    assert "pl-1-2023" not in [a["id"] for a in pd.load(r, "acts.json")]
    assert pd.load(r, "meta.json")["coverage"]["excludedBeforeFirstTerm"] == 1


def test_term_boundaries(con, monkeypatch):
    assert presidency.term_of("2022-12-31") is None
    assert presidency.term_of("2023-01-01") == "2023-2026"
    assert presidency.term_of("2027-01-04") == "2023-2026"
    assert presidency.term_of("2027-01-05") == "2027-2030"
    monkeypatch.setattr(cli, "now", lambda: pd.CLOCK_2027)
    data = pd.terms()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    assert pd.act(con, "mpv-1500-2027")["termId"] == "2023-2026"
    assert pd.act(con, "mpv-1501-2027")["termId"] == "2027-2030"


TERM_2023 = {"id": "2023-2026", "start": "2023-01-01", "end": "2027-01-04", "holder": "Luiz Inácio Lula da Silva",
             "sourceUrl": "https://legis.senado.leg.br/dadosabertos/plenario/resultado/cn/20230101"}
TERM_2027 = {"id": "2027-2030", "start": "2027-01-05", "end": "2031-01-04", "holder": None, "sourceUrl": None}


def test_terms_listed_by_build_date(con, monkeypatch):
    data = pd.recorded()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    assert pd.load(con, "meta.json")["terms"] == [TERM_2023]
    monkeypatch.setattr(cli, "now", lambda: pd.CLOCK_2027)
    data = pd.terms()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    assert pd.load(con, "meta.json")["terms"] == [TERM_2023, TERM_2027]


@pytest.mark.parametrize("value, tramitando, status, rule", [
    ("APROVADO_NA_INTEGRA", "Não", "approved", "mpv.01"),
    ("APROVADO_PLV", "Não", "approvedAmended", "mpv.02"),
    ("PERDA_EFICACIA", "Não", "lapsed", "mpv.03"),
    ("REVOGADO", "Não", "revoked", "mpv.04"),
    ("REJEITADO_PLENARIO", "Não", "rejected", "mpv.05"),
    ("REJEITADO_PLENARIO_CD", "Não", "rejected", "mpv.06"),
    ("INADIMITIDA_URGENCIA", "Não", "rejected", "mpv.07"),
    ("IMPUGNADO_PRESIDENCIA", "Não", "returned", "mpv.08"),
    (None, "Sim", "pending", "mpv.09"),
])
def test_mp_status_rules(value, tramitando, status, rule):
    record = {"tramitando": tramitando} | ({"siglaTipoDeliberacao": value} if value else {})
    assert presidency.mp_status(record, "mpv-1-2023") == (status, rule)


@pytest.mark.parametrize("record", [
    {"siglaTipoDeliberacao": "XYZ", "tramitando": "Não"},
    {"siglaTipoDeliberacao": "SEM_EFICACIA", "tramitando": "Não"},
    {"siglaTipoDeliberacao": "aprovado_plv", "tramitando": "Não"},
    {"tramitando": "Não"},
], ids=["xyz", "sem-eficacia", "wrong-case", "null-not-tramitando"])
def test_mp_status_rules_reject(record):
    with pytest.raises(presidency.PresidencyError):
        presidency.mp_status(record, "mpv-1-2023")


@pytest.mark.parametrize("situacao, status, rule", [
    ("Mantido", "kept", "device.01"), ("Rejeitado", "overridden", "device.02"),
    ("Prejudicado", "prejudged", "device.03"), ("Não Apreciado", "pending", "device.04"),
])
def test_device_status_rules(situacao, status, rule):
    assert presidency.device_status(situacao, "vet-1-2023") == (status, rule)


@pytest.mark.parametrize("situacao", ["Sobrestado", "", "mantido"])
def test_device_status_rules_reject(situacao):
    with pytest.raises(presidency.PresidencyError):
        presidency.device_status(situacao, "vet-1-2023")


def test_recorded_statuses(r):
    acts = {a["id"]: a for a in pd.load(r, "acts.json")}
    expected = {
        "mpv-1154-2023": ("approvedAmended", "mpv.02", "APROVADO_PLV"),
        "mpv-1155-2023": ("lapsed", "mpv.03", "PERDA_EFICACIA"),
        "mpv-1169-2023": ("lapsed", "mpv.03", "PERDA_EFICACIA"),
        "mpv-1177-2023": ("approved", "mpv.01", "APROVADO_NA_INTEGRA"),
        "mpv-1181-2023": ("revoked", "mpv.04", "REVOGADO"),
        "mpv-1350-2026": ("pending", "mpv.09", None),
    }
    for id, (status, rule, official) in expected.items():
        assert (acts[id]["status"], acts[id]["statusRule"], acts[id]["officialStatus"]) == (status, rule, official)
    devices = {
        "17.23.001": ("kept", "device.01", "Mantido"), "49.23.004": ("kept", "device.01", "Mantido"),
        "49.23.001": ("overridden", "device.02", "Rejeitado"), "03.25.004": ("overridden", "device.02", "Rejeitado"),
        "29.25.001": ("overridden", "device.02", "Rejeitado"), "03.26.000": ("overridden", "device.02", "Rejeitado"),
        "29.25.032": ("prejudged", "device.03", "Prejudicado"),
        "03.25.001": ("pending", "device.04", "Não Apreciado"),
    }
    for identifier, (status, rule, official) in devices.items():
        d = pd.device_of(r, identifier)
        assert (d["status"], d["statusRule"], d["officialStatus"]) == (status, rule, official)


def _set_mp(record_id, value):
    def edit(doc):
        next(m for m in doc if m["identificacao"] == record_id)["siglaTipoDeliberacao"] = value
    return edit


def _set_device(identifier, value):
    def edit(doc):
        for d in doc["ResultadoVetoMateriaCN"]["Veto"]["Dispositivos"]["Dispositivo"]:
            if d["Identificador"] == identifier:
                d["Situacao"] = value
    return edit


@pytest.mark.parametrize("path, edit, words", [
    ("/processo?sigla=MPV&ano=2023", _set_mp("MPV 1155/2023", "XYZ"), ["XYZ", "mpv-1155-2023"]),
    ("/plenario/resultado/veto/materia/161861", _set_device("49.23.004", "Sobrestado"), ["Sobrestado", "vet-49-2023"]),
], ids=["mp", "device"])
def test_unknown_status_stops_the_build(con, capsys, path, edit, words):
    data = pd.recorded()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    before = pd.snapshot(pd.out(con))
    pd.serve(con, pd.patch(pd.recorded(), path, edit), houses=False)
    capsys.readouterr()
    assert pd.build4(con, data, "--quiet") == 1
    err = capsys.readouterr().err
    assert all(w in err for w in words)
    assert pd.snapshot(pd.out(con)) == before


def test_veto_status(r):
    acts = {a["id"]: a for a in pd.load(r, "acts.json")}
    assert (acts["vet-3-2025"]["status"], acts["vet-3-2025"]["statusRule"]) == ("pending", "veto.01")
    for id in ("vet-17-2023", "vet-49-2023", "vet-29-2025", "vet-3-2026"):
        assert (acts[id]["status"], acts[id]["statusRule"]) == ("decided", "veto.02")
    assert presidency.veto_status(["kept", "prejudged"]) == ("decided", "veto.02")
    assert presidency.veto_status(["overridden", "pending"]) == ("pending", "veto.01")
    assert presidency.veto_status(["pending"]) == ("pending", "veto.01")


LAW = "Transformado em Norma Jurídica"


@pytest.mark.parametrize("norma, camara, total, status, rule", [
    ("Lei nº 1 de 01/01/2024", "Arquivada", False, "law", "bill.01"),
    (None, LAW, False, "law", "bill.02"),
    (None, "Aguardando Sanção", True, "vetoedTotally", "bill.03"),
    (None, "Arquivada", True, "vetoedTotally", "bill.03"),
    ("Lei nº 1 de 01/01/2024", "Aguardando Sanção", True, "law", "bill.01"),
    (None, LAW, True, "law", "bill.02"),
    (None, "Retirado pelo(a) Autor(a)", False, "withdrawn", "bill.04"),
    (None, "Arquivada", False, "archived", "bill.05"),
    (None, "Aguardando Parecer do Relator na Comissão Especial (CESP)", False, "inProgress", "bill.06"),
    (None, "", False, "inProgress", "bill.06"),
])
def test_bill_status_precedence(norma, camara, total, status, rule):
    assert presidency.bill_status(norma, camara, total) == (status, rule)


def test_recorded_bill_statuses(r):
    acts = {a["id"]: a for a in pd.load(r, "acts.json")}
    for id in ("pl-3626-2023", "plp-93-2023", "pl-6233-2023"):
        assert (acts[id]["status"], acts[id]["statusRule"]) == ("law", "bill.01")
    assert acts["pl-6233-2023"]["officialStatus"] == "Aguardando Encaminhamento"
    assert (acts["pl-1084-2023"]["status"], acts["pl-1084-2023"]["statusRule"]) == ("law", "bill.02")
    assert (acts["pl-9101-2023"]["status"], acts["pl-9101-2023"]["statusRule"]) == ("withdrawn", "bill.04")
    assert (acts["pl-9102-2025"]["status"], acts["pl-9102-2025"]["statusRule"]) == ("archived", "bill.05")
    pec = acts["pec-9103-2026"]
    assert (pec["status"], pec["statusRule"]) == ("inProgress", "bill.06")
    assert pec["officialStatus"] == "Aguardando Parecer do Relator na Comissão Especial (CESP)"


def test_vetoed_totally(h):
    bill = pd.act(h, "pl-9105-2025")
    assert (bill["status"], bill["statusRule"]) == ("vetoedTotally", "bill.03")


def test_law_and_approved_without_law(r, con):
    acts = {a["id"]: a for a in pd.load(r, "acts.json")}
    laws = {"mpv-1154-2023": "Lei nº 14.600 de 19/06/2023", "mpv-1177-2023": "Lei nº 14.696 de 11/10/2023",
            "pl-3626-2023": "Lei nº 14.790 de 29/12/2023", "vet-49-2023": "Lei nº 14.790 de 29/12/2023",
            "plp-93-2023": "Lei Complementar nº 200 de 30/08/2023", "pl-6233-2023": "Lei nº 14.905 de 28/06/2024"}
    for id, law in laws.items():
        assert acts[id]["law"] == law
    for id in ("mpv-1155-2023", "mpv-1169-2023", "mpv-1181-2023", "mpv-1350-2026", "pl-1084-2023"):
        assert acts[id]["law"] is None
    assert pd.load(r, "meta.json")["coverage"]["approvedWithoutLaw"] == 0
    data = pd.hand()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet", "--refresh") == 0  # the raw cache holds R's Câmara files
    mp = pd.act(con, "mpv-1290-2025")
    assert mp["status"] == "approved" and mp["law"] is None
    assert pd.load(con, "meta.json")["coverage"]["approvedWithoutLaw"] == 1


def test_related_act(r, con):
    acts = {a["id"]: a for a in pd.load(r, "acts.json")}
    expected = {
        "vet-17-2023": ({"type": "MPV", "number": 1154, "year": 2023}, "mpv-1154-2023"),
        "vet-49-2023": ({"type": "PL", "number": 3626, "year": 2023}, "pl-3626-2023"),
        "vet-3-2025": ({"type": "PL", "number": 576, "year": 2021}, None),
        "vet-29-2025": ({"type": "PL", "number": 2159, "year": 2021}, None),
        "vet-3-2026": ({"type": "PL", "number": 2162, "year": 2023}, None),
    }
    for id, (matter, related) in expected.items():
        assert (acts[id]["vetoedMatter"], acts[id]["relatedActId"]) == (matter, related)
    for a in acts.values():
        if a["kind"] != "veto":
            assert a["vetoedMatter"] is None and a["relatedActId"] is None
    data = pd.hand()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet", "--refresh") == 0  # the raw cache holds R's Câmara files
    assert pd.act(con, "vet-91-2025")["relatedActId"] == "pl-9105-2025"


def _plain(text):
    return "".join(c for c in unicodedata.normalize("NFKD", text) if not unicodedata.combining(c)).casefold()


RULE_TABLE = [
    ("mpv.01", "APROVADO_NA_INTEGRA", "approved"), ("mpv.02", "APROVADO_PLV", "approvedAmended"),
    ("mpv.03", "PERDA_EFICACIA", "lapsed"), ("mpv.04", "REVOGADO", "revoked"),
    ("mpv.05", "REJEITADO_PLENARIO", "rejected"), ("mpv.06", "REJEITADO_PLENARIO_CD", "rejected"),
    ("mpv.07", "INADIMITIDA_URGENCIA", "rejected"), ("mpv.08", "IMPUGNADO_PRESIDENCIA", "returned"),
    ("mpv.09", None, "pending"),
    ("device.01", "Mantido", "kept"), ("device.02", "Rejeitado", "overridden"),
    ("device.03", "Prejudicado", "prejudged"), ("device.04", "Não Apreciado", "pending"),
    ("veto.01", None, "pending"), ("veto.02", None, "decided"),
    ("bill.01", None, "law"), ("bill.02", "Transformado em Norma Jurídica", "law"), ("bill.03", None, "vetoedTotally"),
    ("bill.04", "Retirado pelo(a) Autor(a)", "withdrawn"), ("bill.05", "Arquivada", "archived"),
    ("bill.06", None, "inProgress"),
]


def test_status_rules_file(r):
    rules = pd.load(r, "status-rules.json")
    assert [(x["id"], x["officialValue"], x["status"]) for x in rules] == RULE_TABLE
    assert len(rules) == 21
    for rule in rules:
        assert set(rule) == {"id", "kind", "source", "field", "officialValue", "status", "description"}
        if rule["officialValue"] is not None:
            assert rule["officialValue"] in rule["description"]
        plain = _plain(rule["description"])
        for word in ("derrota", "vitoria", "fracasso", "importante", "aprovacao do governo", "ranking"):
            assert word not in plain
    by_id = {x["id"]: x["description"] for x in rules}
    assert "tramitando" in by_id["mpv.09"]
    assert "Não Apreciado" in by_id["veto.01"]
    assert "normaGerada" in by_id["bill.01"]
    assert "veto total" in by_id["bill.03"]
    assert pd.load(r, "meta.json")["statusRules"] == {"version": 1}


def test_act_fields(r):
    mp = pd.act(r, "mpv-1154-2023")
    source = next(m for m in pd.recorded_doc("processo-mpv-2023.json") if m["identificacao"] == "MPV 1154/2023")
    assert (mp["issuedAt"], mp["statusAt"], mp["summary"]) == ("2023-01-01", "2023-06-01", source["ementa"])
    veto = pd.act(r, "vet-49-2023")
    assert (veto["issuedAt"], veto["statusAt"]) == ("2023-12-30", "2024-05-09")
    assert pd.act(r, "vet-3-2025")["statusAt"] is None
    bill = pd.act(r, "pl-3626-2023")
    assert (bill["issuedAt"], bill["statusAt"]) == ("2023-07-25", "2024-06-04")
    d = pd.device_of(r, "49.23.001")
    assert d["description"] == "§ 1º do art. 31"
    assert d["text"].startswith("Para os efeitos do disposto neste artigo")
    assert d["reason"].startswith("“A manutenção dos §§1º e 3º")
    assert d["jointRollCallId"] == "49.23.001"
    assert pd.device_of(r, "03.25.001")["jointRollCallId"] is None
    assert pd.device_of(r, "29.25.032")["jointRollCallId"] is None


def test_allowlist_additions(r):
    assert readers.ALLOWLIST["proposicoes"] == ["id", "siglaTipo", "numero", "ano", "ementa", "dataApresentacao",
                                                "ultimoStatus_descricaoSituacao", "ultimoStatus_dataHora"]
    assert readers.ALLOWLIST["proposicoesAutores"] == ["idProposicao", "idDeputadoAutor", "ordemAssinatura",
                                                       "proponente", "codTipoAutor", "nomeAutor"]
    assert not any("cpf" in c.casefold() for columns in readers.ALLOWLIST.values() for c in columns)
    text = "".join(p.read_text() for p in pd.out(r).rglob("*.json"))
    # `tipoAutor` and the deputy author's name are in the served rows but outside the allowlist
    assert "Órgão do Poder Executivo" not in text and "Fulano Deputado" not in text
    assert json.loads((pd.out(r) / "meta.json").read_text())["scope"] == "presidencia"

"""etl-presidencia - the stop-the-build guards added during the build (C61)."""

import presidencia_data as pd
import pytest


def _no_device(doc):
    doc["ResultadoVetoMateriaCN"]["Veto"]["Dispositivos"] = {"Dispositivo": []}


def _device(identifier, **fields):
    def edit(doc):
        for d in doc["ResultadoVetoMateriaCN"]["Veto"]["Dispositivos"]["Dispositivo"]:
            if d["Identificador"] == identifier:
                d.update(fields)
    return edit


def _drop_member(house, member_id):
    def edit(data):
        data["houses"][house][0][:] = [m for m in data["houses"][house][0] if m["id"] != member_id]
    return edit


def _issued(day):
    def edit(doc):
        doc[0]["dataApresentacao"] = day
    return edit


# (Congress document or house members edited, edit, words the stderr must hold)
GUARDS = [
    ("/plenario/resultado/veto/materia/190091", _no_device, ["vet-91-2025", "no device"]),
    ("/plenario/resultado/veto/materia/190090", _device("90.25.003", Situacao="Prejudicado"),
     ["90.25.003", "prejudged", "not kept or overridden"]),
    ("/plenario/resultado/veto/materia/190090", _device("90.25.002", TipoVotacao="Eletrônica"),
     ["90.25.002", "Eletrônica", "not cedula or painel"]),
    ("senado", _drop_member("senado", 5386), ["Prof. Dorinha Seabra/TO", "5386", "absent from senado/members.json"]),
    ("/processo?sigla=MPV&ano=2025", _issued("2027-02-10"), ["mpv-1290-2025", "2027-02-10", "outside every known term"]),
]
IDS = ["veto-without-device", "voted-device-not-decided", "unknown-tipo-votacao", "alias-to-absent-member",
       "act-outside-every-term"]


@pytest.mark.parametrize("target, edit, words", GUARDS, ids=IDS)
def test_guards_stop_the_build_naming_the_record(con, capsys, target, edit, words):
    data = pd.hand()
    if target in data["houses"]:
        edit(data)
    else:
        pd.patch(data, target, edit)
    pd.serve(con, data)
    capsys.readouterr()
    assert pd.build4(con, data, "--quiet") == 1
    err = capsys.readouterr().err
    assert all(w in err for w in words), err

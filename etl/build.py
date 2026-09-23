"""Baixa os dados abertos da Câmara (57ª legislatura) e gera JSONs estáticos para o site.

Uso: python3 etl/build.py [--anos 2023 2024 2025 2026] [--refresh]
Só usa a biblioteca padrão.
"""
import argparse
import csv
import json
import sys
import time
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from collections import Counter, defaultdict
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
RAW = ROOT / "data" / "raw"
OUT = ROOT / "site" / "data"

ARQUIVOS = "https://dadosabertos.camara.leg.br/arquivos/{nome}/csv/{nome}-{ano}.csv"
API = "https://dadosabertos.camara.leg.br/api/v2"

LEGISLATURA = "57"
INICIO_LEGISLATURA = "2023-02-01"
TIPOS_AUTORAIS = {"PL", "PLP", "PEC", "PDL", "PRC"}
VOTOS_VALIDOS = {"Sim", "Não", "Abstenção", "Obstrução"}

csv.field_size_limit(sys.maxsize)


def log(msg):
    print(f"[{time.strftime('%H:%M:%S')}] {msg}", flush=True)


def baixar(nome, ano, refresh=False):
    destino = RAW / f"{nome}-{ano}.csv"
    if destino.exists() and not refresh:
        return destino
    url = ARQUIVOS.format(nome=nome, ano=ano)
    log(f"baixando {url}")
    tmp = destino.with_suffix(".part")
    with urllib.request.urlopen(url, timeout=300) as r, open(tmp, "wb") as f:
        while chunk := r.read(1 << 20):
            f.write(chunk)
    tmp.rename(destino)
    return destino


def ler(nome, anos, refresh=False):
    for ano in anos:
        with open(baixar(nome, ano, refresh), encoding="utf-8-sig", newline="") as f:
            yield from csv.DictReader(f, delimiter=";")


def api_get(path):
    req = urllib.request.Request(API + path, headers={"Accept": "application/json"})
    with urllib.request.urlopen(req, timeout=60) as r:
        return json.load(r)


def deputados_em_exercicio():
    dados = api_get("/deputados?itens=1000")["dados"]
    return {str(d["id"]) for d in dados}


def historico(dep_id, refresh=False):
    """Mudanças de situação na legislatura (posse, licença, reassunção...), com cache local."""
    destino = RAW / "historico" / f"{dep_id}.json"
    if destino.exists() and not refresh:
        return json.loads(destino.read_text())
    for tentativa in range(3):
        try:
            dados = api_get(f"/deputados/{dep_id}/historico")["dados"]
            break
        except Exception:
            if tentativa == 2:
                raise
            time.sleep(2 * (tentativa + 1))
    dados = [
        {"dataHora": h["dataHora"], "situacao": h["situacao"], "descricao": h["descricaoStatus"]}
        for h in dados if str(h["idLegislatura"]) == LEGISLATURA and h["situacao"]
    ]
    destino.write_text(json.dumps(dados, ensure_ascii=False))
    return dados


def periodos_exercicio(hist, fim):
    """[(início, fim)] em que a situação era 'Exercício'."""
    periodos, inicio = [], None
    # alguns registros anteriores à posse vêm marcados com a legislatura atual (ex.: troca de partido em 2022)
    for h in sorted((h for h in hist if h["dataHora"] >= INICIO_LEGISLATURA), key=lambda h: h["dataHora"]):
        if h["situacao"] == "Exercício" and inicio is None:
            inicio = h["dataHora"]
        elif h["situacao"] != "Exercício" and inicio is not None:
            periodos.append((inicio, h["dataHora"]))
            inicio = None
    if inicio is not None:
        periodos.append((inicio, fim))
    return periodos


def maioria_sem(contagem, voto_proprio):
    """Voto majoritário da bancada excluindo o próprio deputado; None se empate ou ninguém mais votou."""
    c = Counter(contagem)
    if voto_proprio in c:
        c[voto_proprio] -= 1
    top = [(v, n) for v, n in c.most_common(2) if n > 0]
    if not top or (len(top) == 2 and top[0][1] == top[1][1]):
        return None
    return top[0][0]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--anos", nargs="+", default=["2023", "2024", "2025", "2026"])
    ap.add_argument("--refresh", action="store_true", help="baixa os CSVs de novo")
    args = ap.parse_args()
    RAW.mkdir(parents=True, exist_ok=True)
    (OUT / "dep").mkdir(parents=True, exist_ok=True)

    # --- votações (metadados) ---
    log("lendo votações")
    votacoes = {}
    for r in ler("votacoes", args.anos, args.refresh):
        if r["data"] < INICIO_LEGISLATURA:
            continue
        votacoes[r["id"]] = {
            "data": r["data"],
            "dataHora": r["dataHoraRegistro"],
            "orgao": r["siglaOrgao"],
            "descricao": r["descricao"].strip(),
            "aprovacao": r["aprovacao"],
            "sim": int(r["votosSim"] or 0),
            "nao": int(r["votosNao"] or 0),
        }

    log("lendo proposições das votações")
    for r in ler("votacoesProposicoes", args.anos, args.refresh):
        v = votacoes.get(r["idVotacao"])
        if v and "prop" not in v:
            v["prop"] = {
                "id": r["proposicao_id"],
                "titulo": r["proposicao_titulo"],
                "ementa": r["proposicao_ementa"].strip(),
            }

    log("lendo orientações")
    orientacoes = defaultdict(dict)  # idVotacao -> {BANCADA: orientação}
    for r in ler("votacoesOrientacoes", args.anos, args.refresh):
        if r["idVotacao"] in votacoes and r["orientacao"]:
            orientacoes[r["idVotacao"]][r["siglaBancada"].upper()] = r["orientacao"]

    # --- votos nominais ---
    log("lendo votos nominais")
    deputados = {}
    votos_por_dep = defaultdict(list)
    votacoes_com_voto = set()
    votos_partido = defaultdict(Counter)  # (idVotacao, partido) -> {voto: n}
    for r in ler("votacoesVotos", args.anos, args.refresh):
        if r["deputado_idLegislatura"] != LEGISLATURA or r["idVotacao"] not in votacoes:
            continue
        dep_id = r["deputado_id"]
        vid = r["idVotacao"]
        votacoes_com_voto.add(vid)
        # o registro mais recente define partido/UF atuais
        atual = deputados.get(dep_id)
        if not atual or r["dataHoraVoto"] >= atual["_ultimo"]:
            deputados[dep_id] = {
                "id": int(dep_id),
                "nome": r["deputado_nome"],
                "partido": r["deputado_siglaPartido"],
                "uf": r["deputado_siglaUf"],
                "foto": r["deputado_urlFoto"],
                "_ultimo": r["dataHoraVoto"],
            }
        votos_por_dep[dep_id].append((vid, r["voto"], r["deputado_siglaPartido"]))
        if r["voto"] in VOTOS_VALIDOS:
            votos_partido[(vid, r["deputado_siglaPartido"])][r["voto"]] += 1

    # --- proposições autorais ---
    log("lendo autores de proposições")
    autoria = defaultdict(dict)  # dep -> {prop_id: é_primeiro_autor}
    for r in ler("proposicoesAutores", args.anos, args.refresh):
        dep_id = r["idDeputadoAutor"]
        if dep_id in deputados and r["proponente"] == "1":
            autoria[dep_id][r["idProposicao"]] = autoria[dep_id].get(r["idProposicao"]) or r["ordemAssinatura"] == "1"

    ids_autorais = {pid for props in autoria.values() for pid in props}
    log("lendo proposições")
    proposicoes = {}
    contagem_req = Counter()
    for r in ler("proposicoes", args.anos, args.refresh):
        if r["id"] not in ids_autorais or r["dataApresentacao"][:10] < INICIO_LEGISLATURA:
            continue
        if r["siglaTipo"] not in TIPOS_AUTORAIS:
            if r["siglaTipo"] in {"REQ", "RIC", "INC"}:
                contagem_req[r["id"]] = 1
            continue
        proposicoes[r["id"]] = [
            int(r["id"]),
            r["siglaTipo"],
            r["numero"],
            r["ano"],
            r["ementa"].strip(),
            r["dataApresentacao"][:10],
            r["ultimoStatus_descricaoSituacao"],
        ]

    # --- saída ---
    log("exportando")
    em_exercicio = deputados_em_exercicio()

    plen_horarios = sorted(
        v["dataHora"] for vid, v in votacoes.items() if vid in votacoes_com_voto and v["orgao"] == "PLEN"
    )
    log(f"baixando histórico de {len(deputados)} deputados")
    (RAW / "historico").mkdir(exist_ok=True)
    with ThreadPoolExecutor(max_workers=6) as pool:
        historicos = dict(zip(deputados, pool.map(lambda i: historico(i, args.refresh), deputados)))
    agora = datetime.now().isoformat(timespec="minutes")

    votacoes_out = {}
    for vid in votacoes_com_voto:
        v = votacoes[vid]
        p = v.get("prop") or {}
        votacoes_out[vid] = [
            v["data"], v["orgao"], v["descricao"], p.get("titulo", ""), p.get("ementa", ""),
            int(p["id"]) if p.get("id") else None, v["aprovacao"], v["sim"], v["nao"],
            orientacoes.get(vid, {}).get("GOVERNO"),
        ]

    lista = []
    for dep_id, d in deputados.items():
        votos = sorted(votos_por_dep[dep_id], key=lambda x: votacoes[x[0]]["data"], reverse=True)
        linhas, gov_total, gov_ok, par_total, par_ok = [], 0, 0, 0, 0
        for vid, voto, partido in votos:
            o_par = maioria_sem(votos_partido[(vid, partido)], voto)
            o_gov = orientacoes.get(vid, {}).get("GOVERNO")
            if voto in VOTOS_VALIDOS:
                if o_gov in VOTOS_VALIDOS:
                    gov_total += 1
                    gov_ok += voto == o_gov
                if o_par in VOTOS_VALIDOS:
                    par_total += 1
                    par_ok += voto == o_par
            linhas.append([vid, voto, partido, o_par])

        # presença: votos no plenário / votações nominais do plenário ocorridas enquanto o deputado estava em exercício
        periodos = periodos_exercicio(historicos[dep_id], agora)
        possiveis = sum(1 for h in plen_horarios if any(ini <= h <= fim for ini, fim in periodos))
        votos_plen = sum(1 for vid, voto, _ in votos if voto and votacoes[vid]["orgao"] == "PLEN")
        presenca = round(min(100, 100 * votos_plen / possiveis), 1) if possiveis else None
        afastamentos = [
            [h["dataHora"][:10], h["descricao"]] for h in historicos[dep_id]
            if h["dataHora"] >= INICIO_LEGISLATURA and h["situacao"] != "Exercício" or "Reassunção" in h["descricao"]
        ]

        props = sorted(
            (proposicoes[pid] + [principal] for pid, principal in autoria[dep_id].items() if pid in proposicoes),
            key=lambda p: p[5], reverse=True,
        )
        n_req = sum(1 for pid in autoria[dep_id] if pid in contagem_req)

        (OUT / "dep" / f"{dep_id}.json").write_text(
            json.dumps({"votos": linhas, "props": props, "afastamentos": afastamentos}, ensure_ascii=False, separators=(",", ":"))
        )
        d.pop("_ultimo")
        d["emExercicio"] = dep_id in em_exercicio
        d["stats"] = {
            "votos": len(votos),
            "presenca": presenca,
            "gov": round(100 * gov_ok / gov_total, 1) if gov_total else None,
            "partido": round(100 * par_ok / par_total, 1) if par_total else None,
            "props": len(props),
            "propsPrincipal": sum(1 for p in props if p[-1]),
            "requerimentos": n_req,
        }
        lista.append(d)

    lista.sort(key=lambda d: d["nome"])
    dump = lambda path, obj: (OUT / path).write_text(json.dumps(obj, ensure_ascii=False, separators=(",", ":")))
    dump("deputados.json", lista)
    dump("votacoes.json", votacoes_out)
    dump("meta.json", {
        "geradoEm": datetime.now(timezone.utc).isoformat(timespec="seconds"),
        "anos": args.anos,
        "votacoes": len(votacoes_out),
        "votacoesPlenario": len(plen_horarios),
        "deputados": len(lista),
        "emExercicio": sum(d["emExercicio"] for d in lista),
    })
    log(f"ok: {len(lista)} deputados, {len(votacoes_out)} votações nominais, {len(proposicoes)} proposições")


if __name__ == "__main__":
    main()

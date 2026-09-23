# Mandato Aberto

Protótipo: o que cada deputado federal fez no mandato (57ª legislatura, desde fev/2023) — votações nominais, projetos de autoria, presença e alinhamento com governo/partido. Base para o cruzamento futuro **promessas de campanha × ações**.

## Rodar

```bash
python3 etl/build.py            # baixa ~700 MB de CSVs da Câmara (cache em data/raw/) e gera site/data/
python3 -m http.server 8765 -d site
# abrir http://localhost:8765
```

Só usa a biblioteca padrão do Python. `--refresh` baixa os CSVs de novo; `--anos 2025 2026` limita o período.

## Fontes

- Arquivos em lote de <https://dadosabertos.camara.leg.br/swagger/api.html#staticfile>: `votacoes`, `votacoesVotos`, `votacoesProposicoes`, `votacoesOrientacoes`, `proposicoes`, `proposicoesAutores` (2023–2026).
- API v2: `/deputados` (quem está em exercício) e `/deputados/{id}/historico` (licenças e reassunções).

A API não tem endpoint de votos por deputado, por isso os votos vêm dos arquivos em lote.

## Métricas

- **Presença**: votos registrados no plenário ÷ votações nominais do plenário ocorridas enquanto o deputado estava em exercício (pelo histórico de licenças). Não distingue falta justificada.
- **Com o governo**: voto igual à orientação da bancada "Governo" (só votações em que o governo orientou Sim/Não/Abstenção/Obstrução).
- **Com o partido**: voto igual à maioria dos *outros* deputados do mesmo partido naquela votação. Não usamos a orientação oficial do partido porque os nomes de blocos vêm truncados nos dados (ex.: `Bl UniPpPsd...`).
- **Proposições**: PL, PLP, PEC, PDL e PRC em que o deputado é proponente (autor principal = 1º signatário). Requerimentos e indicações só entram na contagem.

## Saída (`site/data/`)

- `deputados.json` — lista com estatísticas.
- `votacoes.json` — `{idVotacao: [data, órgão, descrição, título da proposição, ementa, idProposição, aprovada, sim, não, orientação do governo]}`.
- `dep/{id}.json` — `votos: [[idVotacao, voto, partido na época, maioria do partido]]`, `props`, `afastamentos`.

## Próximos passos

1. Arquivar material de campanha dos candidatos a deputado federal antes de 4/out/2026.
2. Extrair promessas (LLM) e ligar cada uma a votações/proposições com evidência (link).
3. Senado, depois assembleias estaduais e câmaras municipais.

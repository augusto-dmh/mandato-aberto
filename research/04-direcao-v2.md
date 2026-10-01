# Direção da v2 — de site de campanha a produto permanente

**Data:** 30/09/2026 · **Entrada:** reflexão do mantenedor em chat em 30/09, `.specs/STATE.md`, `02-grilling-escopo-mvp.md`, `01-pesquisa-juridica.md` · **Status:** direção aceita pelo mantenedor ("Go", 30/09); escopo detalhado da v2 sai do grilling em `05-grilling-escopo-v2.md`.

## O que o mantenedor propôs

1. O MVP não cumpre, no tempo disponível, o objetivo de ser a referência antes da eleição.
2. O valor está em trazer os dados depois do 1º turno e durante o 2º, mesmo que já não sirvam para esta eleição.
3. Reescrever como monolito em framework maduro: Laravel com Vue via Inertia, boas práticas do Laravel desde o início, sem bounded contexts prematuros. Python só onde fizer sentido.
4. Design de excelência (referências: Apple, Spotify), bons números, integração com IA para resumos, exportação dos dados.
5. Mais adiante, uma comunidade que publica dados próprios, com aprovação.
6. Objetivo de fundo: política orientada a dados, contra o voto por popularidade ou parentesco.

## Avaliação

### Premissas corrigidas

- **O MVP está no ar desde 28/09/2026** (`https://augusto-dmh.github.io/mandato-aberto/`). O "antes do 1º turno" foi cumprido; o que não deu tempo foi virar produto forte. O limite é o calendário de oito dias entre pesquisa e deploy, não a aplicação.
- **Deputado federal e senador não têm 2º turno.** Segundo turno é só presidente e governador (25/10/2026). Dado de deputado "durante o 2º turno" não decide voto.
- **A janela real do produto é o mandato 2027–2030.** A 58ª legislatura começa em 01/02/2027: "acompanhe desde o primeiro dia" é o gancho de relançamento. O produto passa de ferramenta de campanha a ferramenta permanente de prestação de contas.
- **A migração já estava prevista.** Decisão 5 de `02-grilling-escopo-mvp.md` e AD-002: o JSON do ETL é o contrato para trocar o site estático por um app Laravel com banco. A mudança antecipa o gatilho, não reverte decisão. ETL e site somam cerca de 2,5 mil linhas; o site é a parte descartável.

### Concordâncias, com ajuste

- **Laravel + Inertia + Vue, monolito.** Contas, alertas, "meus eleitos", exportação, painel de correções, filas para IA e jobs de atualização são backend. Framework dominado pelo mantenedor, com um mantenedor só, é a escolha certa. SSR do Inertia desde o dia 1: o mecanismo de difusão é a URL compartilhada com meta tags.
- **Python fica, só no ETL.** É a parte verificada em perfil `standard` e a que um parlamentar contesta. Reescrever em PHP não ganha nada e perde a verificação. Não vira serviço: é um CLI agendado que publica o JSON; um comando Artisan importa para o banco.
- **Sem over-engineering.** Convenções do Laravel, Postgres, filas, testes. Nada de SPA separada, microserviço ou DDD no dia 1.
- **Design de excelência não vem do framework.** Vem de sistema de design, restrição e iteração. Entra como feature própria, não como acabamento. A direção editorial (decisão 10 do MVP) continua válida como ponto de partida.
- **IA é viável sob as condições da pesquisa jurídica (seção 1 e 2.3):** resumo gerado a partir do dado estruturado, rotulado, versionado por modelo e hash da fonte, revisável antes de publicar; nunca recomendação de voto nem chat "em quem votar" (Res. TSE 23.610, art. 28, §1º-C); nenhuma mídia sintética de pessoa real. A pesquisa recomenda associação com CNPJ antes de IA em escala.
- **Exportação e API pública:** baixo risco, alto valor; o dado é aberto por lei.
- **Comunidade publicando dados:** adiada. Exige moderação, associação e revisão jurídica própria.

### O que muda de verdade

- **Custo e superfície de ataque deixam de ser zero.** AD-001 escolheu estático para não haver o que derrubar em campanha. Depois de 26/10 o motivo enfraquece; em troca, backup, monitoramento e atualizações viram rotina.
- **Legislatura vira entidade de primeira classe.** AD-006 fixa a 57ª. O modelo da v2 nasce com várias legislaturas e com Senado, senão é refeito em fevereiro.
- **As regras de linguagem continuam:** descritivo, fonte em cada número, sem "faltou", sem adjetivo. Depois da eleição cai o risco eleitoral das listas ordenáveis, mas o litígio civil mira adjetivo, não tabela.

## Calendário proposto

| Janela | O que acontece |
|---|---|
| até 04/10/2026 | Site vivo intocado; só a decisão dos nomes em Quem somos (check C68 do `launch`) |
| 05/10 a 26/10 | Grilling e plano da v2, esqueleto Laravel, sistema de design. Nenhuma IA no ar (vedações do TSE de 22 a 26/10) |
| depois de 26/10 | v2 no ar com o resultado da eleição como gancho; depois associação; depois IA |
| 01/02/2027 | Relançamento com a 58ª legislatura |

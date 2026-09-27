---
layout: ../layouts/Prose.astro
title: Metodologia e fontes
description: Como cada número do Mandato Aberto é calculado a partir dos dados abertos da Câmara dos Deputados e do TSE, com o link para cada arquivo oficial.
path: /metodologia/
---

Cada número do site mostra a conta e a base de cálculo, e pode ser refeito à mão a partir dos arquivos oficiais listados em [Fontes](#fontes). Nenhum número é arredondado nem convertido em nota.

O site lista todo deputado com pelo menos um registro de voto na 57ª legislatura, inclusive suplentes e deputados fora de exercício. Em exercício significa que o deputado consta na lista atual de deputados da Câmara.

<h2 id="participacao">Participação em votações nominais do plenário</h2>

Conta: votações nominais do plenário em que o deputado tem registro com qualquer valor: Sim, Não, Abstenção, Obstrução, Art. 17 ou registro em votação secreta.

Base: votações nominais do plenário realizadas enquanto o deputado estava em exercício, segundo o histórico de situações publicado pela Câmara. Só os períodos com situação Exercício entram; licença e qualquer outra situação ficam de fora.

Votações em comissões não entram neste número.

Os dados abertos não informam por que um deputado não tem registro em uma votação. Por isso o site não atribui motivo a nenhum registro que não existe.

Em uma votação secreta, a Câmara registra quem votou, não o voto de cada deputado. Os totais exibidos são os oficiais da Câmara.

<h2 id="alinhamento-governo">Votos iguais à orientação do governo</h2>

Conta: votos Sim, Não, Abstenção ou Obstrução iguais à orientação da bancada GOVERNO na mesma votação.

Base: votos Sim, Não, Abstenção ou Obstrução em votações em que a orientação GOVERNO foi um desses quatro valores.

Orientação Liberado, votação sem orientação registrada, registro Art. 17 e votação secreta ficam fora da conta e da base.

Entram votações nominais do plenário e das comissões.

<h2 id="alinhamento-partido">Votos iguais à maioria do próprio partido</h2>

Conta: votos Sim, Não, Abstenção ou Obstrução iguais ao valor da maioria dos outros deputados do mesmo partido na mesma votação.

O partido é o registrado no voto, não o atual.

A maioria é calculada entre os outros deputados do mesmo partido na mesma votação, sobre os mesmos quatro valores, sem o voto do próprio deputado.

Empate, ou nenhum outro deputado do partido na votação, deixa a votação fora da conta e da base.

Entram votações nominais do plenário e das comissões.

<h2 id="proposicoes">Proposições de autoria</h2>

Conta PL, PLP, PEC, PDL e PRC apresentados a partir de 01/02/2023 em que o deputado consta como proponente.

Primeiro signatário: o deputado é o primeiro na ordem de assinatura.

REQ, RIC e INC são contados à parte, como requerimentos.

<h2 id="candidatura-2026">Candidatura em 2026</h2>

Fonte: conjunto de dados [Candidatos 2026](https://dadosabertos.tse.jus.br/dataset/candidatos-2026), do TSE.

O cruzamento usa nome civil, data de nascimento e UF. O CPF não é lido.

Um deputado que corresponde a mais de uma candidatura não recebe selo.

A situação exibida é a que consta no arquivo do TSE na data da última atualização manual, registrada no histórico do repositório.

<h2 id="fontes">Fontes</h2>

Arquivos anuais de dados abertos da Câmara dos Deputados, no ano de 2023:

- [votacoes-2023.csv](https://dadosabertos.camara.leg.br/arquivos/votacoes/csv/votacoes-2023.csv)
- [votacoesVotos-2023.csv](https://dadosabertos.camara.leg.br/arquivos/votacoesVotos/csv/votacoesVotos-2023.csv)
- [votacoesOrientacoes-2023.csv](https://dadosabertos.camara.leg.br/arquivos/votacoesOrientacoes/csv/votacoesOrientacoes-2023.csv)
- [votacoesProposicoes-2023.csv](https://dadosabertos.camara.leg.br/arquivos/votacoesProposicoes/csv/votacoesProposicoes-2023.csv)
- [proposicoes-2023.csv](https://dadosabertos.camara.leg.br/arquivos/proposicoes/csv/proposicoes-2023.csv)
- [proposicoesAutores-2023.csv](https://dadosabertos.camara.leg.br/arquivos/proposicoesAutores/csv/proposicoesAutores-2023.csv)

Os arquivos dos anos seguintes têm o mesmo nome, com o ano trocado. A 57ª legislatura começou em 01/02/2023.

Outras fontes:

- [deputados.csv](https://dadosabertos.camara.leg.br/arquivos/deputados/csv/deputados.csv), lista de deputados da Câmara
- [/deputados](https://dadosabertos.camara.leg.br/api/v2/deputados) e /deputados/{id}/historico, da API de dados abertos da Câmara (versão 2)
- [Candidatos 2026](https://dadosabertos.tse.jus.br/dataset/candidatos-2026), dados abertos do TSE

Os dados são reconstruídos todos os dias; cada página mostra a data da coleta.

As fotos são as oficiais da Câmara dos Deputados, exibidas sem recorte ou filtro, com o crédito Foto: Câmara dos Deputados.

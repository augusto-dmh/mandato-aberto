# Teste de balanceamento (LGPD) — Mandato Aberto

**Data:** 27/09/2026 · **Versão:** rascunho para revisão dos mantenedores antes do lançamento
**Controladores:** [a definir] (pessoas físicas identificadas em `site/src/lib/site.ts` e na página Quem somos do site)
**Base legal avaliada:** legítimo interesse (art. 7º, IX da Lei 13.709/2018), combinado com o tratamento de dados de acesso público (art. 7º, §3º) e o tratamento posterior para finalidade compatível (art. 7º, §7º); a publicidade dos dados vem do art. 8º da Lei 12.527/2011 (LAI)
**Estrutura:** Anexo II do Guia Orientativo sobre Legítimo Interesse da ANPD (2024), como a pesquisa jurídica o lê (`01-pesquisa-juridica.md`, seção 3.1)

> Rascunhado por IA a partir da pesquisa jurídica e do que o repositório documenta. Não substitui parecer de advogado. Os mantenedores revisam cada frase antes do lançamento.

## Finalidade

Dar acesso público aos atos do mandato de cada deputado federal: votos em votações nominais, participação nessas votações, proposições de autoria e, para quem é candidato em 2026, cargo, partido, número e situação da candidatura no TSE.

- **Interesse legítimo:** o controle social do mandato. Os guias da ANPD lidos na pesquisa (Poder Público, 2022; Legítimo Interesse, 2024) tratam dados de agentes públicos como sujeitos ao escrutínio da sociedade por decorrência do exercício da atividade pública.
- **Situação concreta:** os dados já são publicados pela Câmara dos Deputados e pelo TSE; o site os reúne por deputado, com a base de cálculo de cada número e o link para o registro oficial.
- **Finalidade compatível (art. 7º, §7º):** a Câmara e o TSE publicam esses dados para dar transparência ao mandato e à eleição; o site usa os mesmos dados para a mesma transparência, sem outro uso.
- **Não é:** propaganda, avaliação de candidato, recomendação de voto, perfil comportamental ou venda de dados. O site não tem anúncios, não recebe recursos de partidos, candidatos ou campanhas e não publica conteúdo gerado por IA (AD-007, AD-009).

## Necessidade

Só entram os campos sem os quais a finalidade não se cumpre:

| Campo | Por que é necessário |
| --- | --- |
| nome parlamentar, partido, UF, foto oficial | identificar o deputado como a própria Câmara o apresenta |
| períodos em exercício | base de cálculo da participação: só conta o tempo em exercício |
| votos em votações nominais | o ato do mandato que o site mostra |
| proposições de autoria | o outro ato do mandato que o site mostra |
| cargo, partido, número e situação no TSE | informar se o deputado disputa a eleição de 2026 |
| nome civil e data de nascimento (Câmara e TSE) | lidos só para cruzar a Câmara com o TSE sem CPF; nunca exibidos nem gravados no contrato publicado |

Ficam de fora, por não serem necessários: CPF (AD-003), telefone, endereço, e-mail, cor ou raça, religião e qualquer outro campo das fontes. O ETL lê cada arquivo por uma lista fechada de colunas, e o arquivo do TSE, que traz CPF, nunca entra no repositório: a integração contínua lê só `etl/inputs/candidacy-2026.json`, com os quatro campos da candidatura por deputado (AD-011).

Não há alternativa menos invasiva que cumpra a finalidade: mostrar o mandato sem identificar o deputado não permite o controle social que justifica o tratamento.

## Balanceamento

- **Expectativa do titular:** um deputado federal espera que votos, proposições e situação de candidatura sejam públicos; a Constituição, a LAI e o regimento da Câmara os tornam públicos. A foto é a oficial, publicada pela Câmara para identificação.
- **Natureza dos dados:** atos oficiais. A sigla partidária é dado do mandato e da candidatura, publicado por lei; o risco apontado pela pesquisa está em inferir opinião política, e o site não infere: mostra contagens sobre atos oficiais, cada uma com a base de cálculo e a metodologia pública.
- **Impactos possíveis:** um erro de dado ou uma leitura que pareça juízo sobre o parlamentar. Mitigação: linguagem descritiva verificada por teste (nenhum adjetivo, nenhum percentual, nenhum termo da lista proibida), link para a fonte oficial em cada registro e canal de correção com prazos.
- **Titulares não parlamentares:** o site não trata dado de visitante além do que a hospedagem processa para servir as páginas; o formulário de erro não envia nada ao site.
- **Conclusão:** o interesse legítimo prevalece, porque os dados já são públicos por lei, o uso é o mesmo que motivou a publicação, nada além do necessário é tratado e o titular tem canal para corrigir e responder.

## Salvaguardas

- **Transparência (art. 9º):** página Dados e privacidade com campos, finalidade, base legal, controladores e canal; página Metodologia e fontes com a conta de cada número.
- **Direitos do titular (art. 18):** o e-mail de correções atende também aos pedidos do art. 18; triagem em até 48 horas e correção ou publicação da resposta do parlamentar em até 7 dias, na página Correções, versionada no repositório (AD-008).
- **Minimização:** CPF nunca lido nem gravado; lista fechada de colunas em cada leitura; nome civil e data de nascimento só no cruzamento.
- **Visitantes:** nenhum cookie e nada gravado no navegador; contagem de visitas pelo Cloudflare Web Analytics, sem cookie e sem identificador individual; nenhum formulário com servidor.
- **Proveniência:** cada registro traz o link para a fonte oficial e cada página a data da coleta; os arquivos brutos baixados são guardados com hash (AD-005).
- **Agente de pequeno porte:** os controladores são pessoas naturais; pela Resolução CD/ANPD 2/2022, dispensados de encarregado, mantêm o canal de comunicação acima.
- **Revisão:** este teste é revisto a cada nova fonte ou novo campo, e antes de qualquer tratamento por IA (AD-009).

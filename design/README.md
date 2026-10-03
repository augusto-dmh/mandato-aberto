# mandato-design

Design system of the Mandato Aberto v2: one token source, the product's vote encoding and the components that render its recurring units. Plan and checks in `.specs/features/design-system/`; the research behind every rule in `research/06-pesquisa-design-e-concorrentes.md` (principles P1 to P15 below refer to its section 3).

```bash
npm install
npm run build        # tokens/*.json -> dist/tokens.css; fails on a contrast pair below its floor
npm run prototype    # dist/prototype/<direction>/{profile,roll-call,card}.html from MANDATO_DATA (default ../data/out)
npm test             # tokens, components, prototype, package
npx playwright install chromium && npm run test:e2e   # layout and computed style in a browser
```

Pages set `data-direction="<direction>"` on `<html>` and load `tokens.css`, `styles/components.css` and the fonts. The theme follows the system; `data-theme="light"` or `"dark"` forces one.

## Tokens

The system has one direction, **Plenário** (`.specs/STATE.md` AD-015): Archivo with its width axis for display and interface, Source Serif 4 for long text, achromatic neutrals and one violet accent. Never set a deputy's name in capitals, and never use an extreme weight in a sentence about a person.


`tokens/base.json` holds spacing, layout, motion and the photo's native size; each other file is one direction (W3C Design Tokens format). A colour token carries its dark value and its role in `$extensions.mandato`: `surface`, `text` (4.5:1 against every surface), `graphic` (3:1) or `decor` (no information, no floor). CSS names are `--ma-<group>-<name>`. Never add a colour named after a vote option, a party or a valence.

## Vote encoding

Position and shape carry the option; colour never does (P6). Baseline at mid-height.

| Contract value | Mark | Label |
| --- | --- | --- |
| `Sim` | filled bar above the baseline | votou Sim |
| `Não` | filled bar below the baseline | votou Não |
| `Abstenção` | hollow square on the baseline | votou Abstenção |
| `Obstrução` | hatched square on the baseline | votou Obstrução |
| `Artigo 17` | small filled dot on the baseline | Art. 17 (presidente da sessão) |
| empty, open ballot | gap | Registro sem voto |
| empty, secret ballot | gap | Votação secreta |
| anything else | small hollow dot | the raw value |

Contract v3 carries a `position` beside the official value, and `positionCase({house, position, official})` draws the same marks from it (`.specs/features/app-contract-v3/plan.md`, doors 5 and 6). Only the labels depend on the house:

| Position | Mark | Câmara | Senado |
| --- | --- | --- | --- |
| `yes`, `no`, `abstention`, `obstruction` | as `Sim`, `Não`, `Abstenção`, `Obstrução` above | Sim, Não, Abstenção, Obstrução | the same |
| `presiding` | small filled dot on the baseline | Art. 17 (presidente da sessão) | Presidente da sessão (art. 51 RISF) |
| `secret` | gap | Votação secreta | Votou (votação secreta) |
| `notVoting` | gap | Registro sem voto | Sem voto: the Senate's description of the code (`P-NRV`, `AP`, `MIS`, `NCom`, `NA`, `Licença`), or the code itself; without the official value, Não registrou voto |

## Components

### NDeM

- **Inputs:** `count`, `total`, `label`, `note` (`index`, `sourceUrl`, `methodUrl`).
- **Empty state:** with `total` 0 it writes "Sem base de cálculo no período" and no number; the note stays.
- **Principle:** P5, "n de m" is the unit, and P1, every number carries its note. The count is set in display type and "de m" sits on the same baseline. No percentage is shown.

### SourceNote

- **Inputs:** `index`, `sourceUrl`, `sourceLabel` (default "Câmara dos Deputados"), `methodUrl`, `collectedAt`.
- **Empty state:** without `methodUrl` it shows only the source link.
- **Principle:** P1 and P2. The note sits inside the frame, right below the number, and links to the official source and the method.

### VoteMark

- **Inputs:** `vote` (contract value), `secret`, `who` ("Nome, PARTIDO-UF"); for contract v3, `house`, `position`, `official` instead of `vote` and `secret`.
- **Empty state:** an empty vote is a gap with its label ("Registro sem voto" or "Votação secreta").
- **Principle:** P6. Shape and position carry the vote, every stroke is `currentColor`, and the option is always exposed as text.

### MandateScore

- **Inputs:** `votes` (`rollCallId`, `date`, `title`, `vote`, `secret`), `href`, `compact`; for contract v3, `house` and votes carrying `position` and `official`, with the legend in that house's labels.
- **Empty state:** with no votes it renders no row and an empty table.
- **Principle:** P13 and P14. It shows one column per roll call, oldest first, one row per year, each linking to its roll call. The equivalent table carries the same data (P10, P11). The compact form is the card's.

### OfficialPhoto

- **Inputs:** `src`, `name`, `credit` (default "Foto: Câmara dos Deputados").
- **Missing-data state:** without `src` it shows the name's initials in a frame of the same size, never a silhouette.
- **Principle:** P9. The photo is 3:4, never larger than 354 × 472, with no filter, blend, transform or crop.

### TallyBar

- **Inputs:** `yes`, `no`, `others`.
- **Empty state:** zero counts render no cells and the written zeros.
- **Principle:** P5. There is one cell per vote, and the counts are always written out.

### AiSummaryFrame

- **Inputs:** `text`, `reviewedAt`, `officialUrl`, `reportUrl`, `sample`.
- **Empty state:** without `reviewedAt` it renders nothing. An unreviewed summary is never shown.
- **Principle:** research section 4. The frame is distinct and labelled "Resumo gerado por IA a partir do texto oficial, revisado em DD/MM/AAAA", with links to the full text and to the error report.

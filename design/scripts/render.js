// Loaded through Vite's SSR module loader so the .vue screens compile; renders one full HTML page.
import { createSSRApp, h } from "vue";
import { renderToString } from "vue/server-renderer";

import Card from "../screens/Card.vue";
import Profile from "../screens/Profile.vue";
import RollCall from "../screens/RollCall.vue";

const SCREENS = { profile: Profile, "roll-call": RollCall, card: Card };
const TITLES = { plenario: "Plenário" };

export async function renderPage(screen, direction, props) {
  const body = await renderToString(
    createSSRApp({ render: () => h(SCREENS[screen], { ...props, direction: TITLES[direction] ?? direction }) }),
  );
  return `<!doctype html>
<html lang="pt-BR" data-direction="${direction}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${props.title} · protótipo ${TITLES[direction] ?? direction}</title>
<link rel="stylesheet" href="../fonts/fonts.css">
<link rel="stylesheet" href="../tokens.css">
<link rel="stylesheet" href="../components.css">
</head>
<body class="ma-page">
${body}
</body>
</html>
`;
}

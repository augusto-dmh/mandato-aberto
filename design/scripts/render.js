// Loaded through Vite's SSR module loader so the .vue screens compile; renders one full HTML page.
import { createSSRApp, h } from "vue";
import { renderToString } from "vue/server-renderer";

import MemberCard from "../components/MemberCard.vue";
import RollCallCard from "../components/RollCallCard.vue";
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

/** One share card alone on a page, at the top left, as the app's renderer draws it (share-cards door 4). */
export async function renderCardPage(kind, props) {
  const component = kind === "roll_call" ? RollCallCard : MemberCard;
  const body = await renderToString(createSSRApp({ render: () => h(component, props) }));
  return `<!doctype html>
<html lang="pt-BR" data-direction="plenario" data-theme="light">
<head>
<meta charset="utf-8">
<title>card</title>
<link rel="stylesheet" href="../fonts/fonts.css">
<link rel="stylesheet" href="../tokens.css">
<link rel="stylesheet" href="../components.css">
<style>body { margin: 0; }</style>
</head>
<body class="ma-page">
${body}
</body>
</html>
`;
}

import "../css/app.css";

import { createInertiaApp, router } from "@inertiajs/vue3";

createInertiaApp({ pages: "./Pages" });

// The share tags are written by the server (plan door 8); a client visit only has to keep the tab title in step.
router.on("navigate", (event) => {
    document.title = `${event.detail.page.props.meta.title} - Mandato Aberto`;
});

import vue from "@vitejs/plugin-vue";
import { defineConfig } from "vite";

// The card renderer (share-cards door 3): a Node CLI built beside the Inertia SSR bundle, never served.
export default defineConfig({
    plugins: [vue()],
    publicDir: false,
    resolve: { dedupe: ["vue"] },
    ssr: { noExternal: ["mandato-design"] },
    build: {
        ssr: "resources/js/cards/render.js",
        outDir: "bootstrap/cards",
        emptyOutDir: true,
        rolldownOptions: { output: { entryFileNames: "render.mjs" } },
    },
});

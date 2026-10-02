import inertia from "@inertiajs/vite";
import vue from "@vitejs/plugin-vue";
import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/js/app.js", "resources/css/app.css"],
            ssr: "resources/js/ssr.js",
            refresh: true,
        }),
        inertia(),
        vue(),
    ],
    // The design package is linked from ../design: one Vue for both, and bundled into the SSR build.
    resolve: { dedupe: ["vue"] },
    ssr: { noExternal: ["mandato-design"] },
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});

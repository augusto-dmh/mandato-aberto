import vue from "@astrojs/vue";
import { defineConfig } from "astro/config";

// SITE_URL stays a placeholder until the launch feature registers the .org domain.
export default defineConfig({
  site: process.env.SITE_URL || "https://mandatoaberto.org",
  output: "static",
  trailingSlash: "always",
  integrations: [vue()],
  vite: {
    // Scripts ship as files under `/_astro/`, never inlined, so a build test can read every script a page runs.
    build: { assetsInlineLimit: (file) => (file.endsWith(".js") ? false : undefined) },
  },
});

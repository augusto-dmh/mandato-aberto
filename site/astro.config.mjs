import { writeFileSync } from "node:fs";

import vue from "@astrojs/vue";
import { defineConfig } from "astro/config";

/** `dist/_redirects` for Cloudflare Pages: `www` to the apex of the configured site (plan door 1). */
function hostRedirects() {
  let site;
  return {
    name: "mandato-host-redirects",
    hooks: {
      "astro:config:done": ({ config }) => {
        site = new URL(config.site);
      },
      "astro:build:done": ({ dir }) => {
        writeFileSync(new URL("_redirects", dir), `https://www.${site.host}/* ${site.origin}/:splat 301\n`);
      },
    },
  };
}

// SITE_URL stays a placeholder until the launch feature registers the .org domain.
export default defineConfig({
  site: process.env.SITE_URL || "https://mandatoaberto.org",
  output: "static",
  trailingSlash: "always",
  integrations: [vue(), hostRedirects()],
  vite: {
    // Scripts ship as files under `/_astro/`, never inlined, so a build test can read every script a page runs.
    build: { assetsInlineLimit: (file) => (file.endsWith(".js") ? false : undefined) },
  },
});

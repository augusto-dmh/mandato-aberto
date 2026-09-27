import vue from "@astrojs/vue";
import { defineConfig } from "astro/config";

// SITE_URL stays a placeholder until the launch feature registers the .org domain.
export default defineConfig({
  site: process.env.SITE_URL || "https://mandatoaberto.org",
  output: "static",
  trailingSlash: "always",
  integrations: [vue()],
});

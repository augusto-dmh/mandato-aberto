import { defineConfig } from "@playwright/test";

const PORT = 4174;

export default defineConfig({
  testDir: "e2e",
  globalSetup: "./e2e/setup.ts",
  use: { baseURL: `http://localhost:${PORT}`, browserName: "chromium" },
  webServer: { command: `node scripts/serve.mjs dist/e2e ${PORT}`, port: PORT, reuseExistingServer: false },
  reporter: "list",
});

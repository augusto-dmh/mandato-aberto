import { getViteConfig } from "astro/config";

export default getViteConfig({
  test: {
    include: ["tests/**/*.test.ts"],
    hookTimeout: 300_000,
    testTimeout: 30_000,
  },
});

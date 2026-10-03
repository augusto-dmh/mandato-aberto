// First-load budget of the home, search and overview in Chromium (app-home AC 41, check C44).
//
//   node tests/budget/first-load.mjs [target]    target defaults to http://localhost:8093
//
// Needs the budget dataset in the target's database (`sail artisan migrate:fresh --force` then
// `sail artisan db:seed --class=BudgetSeeder --force`), the SSR server running, and Playwright from the
// design package (`npm ci --prefix ../design`, `npx --prefix ../design playwright install chromium`).
//
// `php artisan serve` sends nothing compressed, and the production server will gzip text, as the HTML budget
// (AC 39) assumes. So the pages load through a local proxy that gzips text responses at level 6 and passes
// the Host header through, which keeps every asset URL the app writes on the proxy's own origin.
// Exits 1 when a page exceeds its budget or any request leaves the page's origin.

import http from "node:http";
import { createRequire } from "node:module";
import { gzipSync } from "node:zlib";

const require = createRequire(new URL("../../../design/package.json", import.meta.url));
const { chromium } = require("playwright");

const target = new URL(process.argv[2] ?? "http://localhost:8093");
const BUDGETS = { "/": 125_000, "/busca/": 130_000, "/legislaturas/58/": 135_000 };
const TEXT = /^(text\/|application\/(javascript|json)|image\/svg\+xml)/;

const proxy = http.createServer((req, res) => {
  const upstream = http.request(
    { host: target.hostname, port: target.port || 80, path: req.url, method: req.method, headers: { ...req.headers, "accept-encoding": "identity" } },
    (up) => {
      const chunks = [];
      up.on("data", (c) => chunks.push(c));
      up.on("end", () => {
        let body = Buffer.concat(chunks);
        const headers = { ...up.headers };
        delete headers["transfer-encoding"];
        if (TEXT.test(headers["content-type"] ?? "") && /\bgzip\b/.test(req.headers["accept-encoding"] ?? "")) {
          body = gzipSync(body, { level: 6 });
          headers["content-encoding"] = "gzip";
          headers.vary = "Accept-Encoding";
        }
        headers["content-length"] = String(body.length);
        res.writeHead(up.statusCode ?? 502, headers);
        res.end(body);
      });
    },
  );
  upstream.on("error", (e) => {
    res.writeHead(502);
    res.end(String(e));
  });
  req.pipe(upstream);
});
await new Promise((resolve) => proxy.listen(0, "127.0.0.1", resolve));
const origin = `http://localhost:${proxy.address().port}`;

const browser = await chromium.launch();
let failed = false;
try {
  for (const [path, budget] of Object.entries(BUDGETS)) {
    const context = await browser.newContext();
    const page = await context.newPage();
    const cdp = await context.newCDPSession(page);
    await cdp.send("Network.enable");
    await cdp.send("Network.setCacheDisabled", { cacheDisabled: true });
    const urls = new Map();
    let total = 0;
    cdp.on("Network.requestWillBeSent", (e) => urls.set(e.requestId, e.request.url));
    cdp.on("Network.loadingFinished", (e) => {
      total += e.encodedDataLength;
    });
    const response = await page.goto(origin + path, { waitUntil: "networkidle" });
    await context.close();

    const foreign = [...urls.values()].filter((u) => new URL(u).origin !== origin);
    const ok = response?.status() === 200 && total <= budget && foreign.length === 0;
    failed ||= !ok;
    console.log(`${ok ? "ok  " : "FAIL"} ${path} ${total} bytes of ${budget}, ${urls.size} requests${foreign.length ? `, other origins: ${foreign.join(" ")}` : ""}`);
  }
} finally {
  await browser.close();
  proxy.close();
}
process.exit(failed ? 1 : 0);

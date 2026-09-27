import { existsSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { createServer, type Server } from "node:http";
import type { AddressInfo } from "node:net";
import { tmpdir } from "node:os";
import { join } from "node:path";
import { afterAll, beforeAll, describe, expect, it } from "vitest";

import { fetchPhotos } from "../src/lib/photos";

const JPEG = readFileSync(join(__dirname, "fixtures", "photos", "101.jpg"));
const requests: string[] = [];
let server: Server;
let base: string;

beforeAll(async () => {
  server = createServer((req, res) => {
    requests.push(req.url!);
    if (req.url === "/ok.jpg") {
      res.writeHead(200, { "Content-Type": "image/jpeg" }).end(JPEG);
    } else if (req.url === "/html.jpg") {
      res.writeHead(200, { "Content-Type": "text/html" }).end("<html>erro</html>");
    } else if (req.url === "/slow.jpg") {
      setTimeout(() => res.writeHead(200).end(JPEG), 1_000);
    } else {
      res.writeHead(404).end();
    }
  });
  await new Promise<void>((done) => server.listen(0, "127.0.0.1", done));
  base = `http://127.0.0.1:${(server.address() as AddressInfo).port}`;
});
afterAll(() => {
  server.closeAllConnections();
  server.close();
});

const cache = () => mkdtempSync(join(tmpdir(), "mandato-photos-"));

describe("photo download", () => {
  it("writes a 200 JPEG byte-identical and returns its path", async () => {
    const dir = cache();
    const photos = await fetchPhotos([{ id: 1, photoUrl: `${base}/ok.jpg` }], { cacheDir: dir, enabled: true });
    expect(photos.get(1)).toBe(join(dir, "1.jpg"));
    expect(readFileSync(join(dir, "1.jpg")).equals(JPEG)).toBe(true);
    rmSync(dir, { recursive: true });
  });

  it("returns null and leaves no file on 404, non-JPEG body and timeout", async () => {
    const dir = cache();
    const photos = await fetchPhotos(
      [
        { id: 2, photoUrl: `${base}/missing.jpg` },
        { id: 3, photoUrl: `${base}/html.jpg` },
        { id: 4, photoUrl: `${base}/slow.jpg` },
      ],
      { cacheDir: dir, enabled: true, timeoutMs: 200 },
    );
    for (const id of [2, 3, 4]) {
      expect(photos.get(id)).toBeNull();
      expect(existsSync(join(dir, `${id}.jpg`))).toBe(false);
    }
    rmSync(dir, { recursive: true });
  });

  it("issues no request for a cached photo", async () => {
    const dir = cache();
    writeFileSync(join(dir, "5.jpg"), JPEG);
    requests.length = 0;
    const photos = await fetchPhotos([{ id: 5, photoUrl: `${base}/ok.jpg` }], { cacheDir: dir, enabled: true });
    expect(requests).toEqual([]);
    expect(photos.get(5)).toBe(join(dir, "5.jpg"));
    rmSync(dir, { recursive: true });
  });

  it("issues no request when downloads are off", async () => {
    const dir = cache();
    writeFileSync(join(dir, "6.jpg"), JPEG);
    requests.length = 0;
    const photos = await fetchPhotos(
      [
        { id: 6, photoUrl: `${base}/ok.jpg` },
        { id: 7, photoUrl: `${base}/ok.jpg` },
      ],
      { cacheDir: dir, enabled: false },
    );
    expect(requests).toEqual([]);
    expect(photos.get(6)).toBe(join(dir, "6.jpg"));
    expect(photos.get(7)).toBeNull();
    rmSync(dir, { recursive: true });
  });
});

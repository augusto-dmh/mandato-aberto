/**
 * Official Câmara photos, downloaded once into a local cache and served from our origin (door 4).
 * The bytes are kept exactly as downloaded: never resized, cropped or filtered.
 */
import { existsSync, mkdirSync, renameSync, writeFileSync } from "node:fs";
import { join, resolve } from "node:path";

import { contract } from "./data";

export interface PhotoOptions {
  cacheDir: string;
  /** false: use the cache only, request nothing (`MANDATO_PHOTOS=off`). */
  enabled: boolean;
  timeoutMs?: number;
  concurrency?: number;
}

const USER_AGENT = "mandato-aberto-site (+https://github.com/augusto-dmh/mandato-aberto)";
const isJpeg = (bytes: Uint8Array) => bytes[0] === 0xff && bytes[1] === 0xd8 && bytes[2] === 0xff;

async function download(url: string, target: string, timeoutMs: number): Promise<boolean> {
  try {
    const response = await fetch(url, { signal: AbortSignal.timeout(timeoutMs), headers: { "User-Agent": USER_AGENT } });
    if (!response.ok) return false;
    const bytes = new Uint8Array(await response.arrayBuffer());
    if (!isJpeg(bytes)) return false;
    writeFileSync(`${target}.part`, bytes);
    renameSync(`${target}.part`, target);
    return true;
  } catch {
    return false;
  }
}

/** Local path of each deputy's photo, or `null` when it is neither cached nor downloadable. */
export async function fetchPhotos(
  deputies: { id: number; photoUrl: string }[],
  { cacheDir, enabled, timeoutMs = 15_000, concurrency = 8 }: PhotoOptions,
): Promise<Map<number, string | null>> {
  mkdirSync(cacheDir, { recursive: true });
  const photos = new Map<number, string | null>();
  const queue = [...deputies];
  const failed: number[] = [];
  const worker = async () => {
    for (let d = queue.shift(); d; d = queue.shift()) {
      const path = join(cacheDir, `${d.id}.jpg`);
      if (existsSync(path)) photos.set(d.id, path);
      else if (enabled && (await download(d.photoUrl, path, timeoutMs))) photos.set(d.id, path);
      else {
        photos.set(d.id, null);
        if (enabled) failed.push(d.id);
      }
    }
  };
  await Promise.all(Array.from({ length: concurrency }, worker));
  if (failed.length) console.warn(`photos: ${failed.length} could not be downloaded (${failed.slice(0, 10).join(", ")})`);
  return photos;
}

let resolved: Promise<Map<number, string | null>> | undefined;

/** Photos of every deputy in the contract, resolved once per build. */
export function photos(): Promise<Map<number, string | null>> {
  resolved ??= fetchPhotos(contract().deputies, {
    cacheDir: resolve(process.cwd(), process.env.MANDATO_PHOTO_CACHE || ".cache/photos"),
    enabled: process.env.MANDATO_PHOTOS !== "off",
  });
  return resolved;
}

/** Public path of a deputy's photo, or `null` when there is none. */
export async function photoPath(id: number): Promise<string | null> {
  return (await photos()).get(id) ? `/fotos/${id}.jpg` : null;
}

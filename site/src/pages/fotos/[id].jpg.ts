import type { APIRoute } from "astro";
import { readFileSync } from "node:fs";

import { photos } from "../../lib/photos";

/** Only deputies whose photo is cached get a file; any other id is the host's 404 (door 4). */
export async function getStaticPaths() {
  const cached = await photos();
  return [...cached].filter(([, path]) => path).map(([id, path]) => ({ params: { id: String(id) }, props: { path } }));
}

export const GET: APIRoute = ({ props }) =>
  new Response(new Uint8Array(readFileSync(props.path as string)), { headers: { "Content-Type": "image/jpeg" } });

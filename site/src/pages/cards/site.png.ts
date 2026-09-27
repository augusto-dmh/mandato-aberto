import type { APIRoute } from "astro";

import { renderCard, siteCardTree } from "../../lib/cards";
import { contract } from "../../lib/data";

export const GET: APIRoute = async () => {
  const png = await renderCard(siteCardTree(contract().meta));
  return new Response(new Uint8Array(png), { headers: { "Content-Type": "image/png" } });
};

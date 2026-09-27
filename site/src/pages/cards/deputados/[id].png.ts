import type { APIRoute } from "astro";

import { cardTree, photoForCard, renderCard } from "../../../lib/cards";
import { contract } from "../../../lib/data";
import { photos } from "../../../lib/photos";

export function getStaticPaths() {
  return contract().deputies.map((d) => ({ params: { id: String(d.id) } }));
}

export const GET: APIRoute = async ({ params }) => {
  const { meta, deputies } = contract();
  const deputy = deputies.find((d) => d.id === Number(params.id))!;
  const photo = photoForCard((await photos()).get(deputy.id) ?? null);
  const png = await renderCard(cardTree(deputy, meta, photo));
  return new Response(new Uint8Array(png), { headers: { "Content-Type": "image/png" } });
};

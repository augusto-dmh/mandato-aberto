/**
 * The one way a page writes a link inside the site (AD-012): the site may be served under a base
 * path such as `/mandato-aberto/`. Runs at build time and in the browser, where Vite inlines the base.
 */
export function withBase(path: string): string {
  const base = import.meta.env.BASE_URL;
  if (base === "/") return path;
  return `${base.replace(/\/+$/, "")}/${path.replace(/^\/+/, "")}`;
}

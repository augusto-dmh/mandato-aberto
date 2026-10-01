import { Window } from "happy-dom";
import { createSSRApp, h, type Component } from "vue";
import { renderToString } from "vue/server-renderer";

export async function render(component: Component, props: Record<string, unknown> = {}) {
  const html = await renderToString(createSSRApp({ render: () => h(component, props) }));
  const window = new Window();
  window.document.body.innerHTML = html;
  return { html, doc: window.document as unknown as Document };
}

/** Text content with every element matching `skip` removed first. */
export function textWithout(root: Element, skip: string) {
  const clone = root.cloneNode(true) as Element;
  for (const el of clone.querySelectorAll(skip)) el.remove();
  return clone.textContent ?? "";
}

/**
 * Who runs the site and where to write to them. Pages and tests read these values from here only.
 * `[a definir]` until the maintainers fill them in the launch PR; the deployed site must not show it.
 */
export interface Maintainer {
  name: string;
  city: string;
}

export const MAINTAINERS: Maintainer[] = [{ name: "[a definir]", city: "[a definir]" }];

/** One dedicated address for contact, corrections and the rights of art. 18 of the LGPD. */
export const CORRECTIONS_EMAIL = "[a definir]";

export const REPO_URL = "https://github.com/augusto-dmh/mandato-aberto";

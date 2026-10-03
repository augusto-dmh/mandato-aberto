// Copy shared by the share cards (share-cards door 4); the app's `Labels` holds the same values for its pages.

export const HOUSE_NAMES = { camara: "Câmara dos Deputados", senado: "Senado Federal" };

export const PHOTO_CREDITS = { camara: "Foto: Câmara dos Deputados", senado: "Foto: Agência Senado" };

export const BALLOTS = { nominal: "Votação nominal", secret: "Votação secreta", symbolic: "Votação simbólica" };

export const KINDS = {
  final: "Decisão sobre a proposta",
  amendment: "Emenda, destaque ou parte do texto",
  procedural: "Procedimento",
  unclassified: "Sem regra correspondente",
};

/** The three card formats: name in the props, size in pixels, name in the URL. */
export const FORMATS = {
  og: { width: 1200, height: 630, path: "1200x630" },
  feed: { width: 1080, height: 1350, path: "1080x1350" },
  story: { width: 1080, height: 1920, path: "1080x1920" },
};

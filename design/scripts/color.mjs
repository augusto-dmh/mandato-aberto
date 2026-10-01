// OKLCH -> sRGB and WCAG 2.2 contrast, from the published OKLab matrices (Björn Ottosson, 2020).

const OKLCH = /^oklch\(\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\)$/;

export function parseOklch(value) {
  const match = OKLCH.exec(value.trim());
  if (!match) throw new Error(`not an oklch() colour: ${value}`);
  return match.slice(1, 4).map(Number);
}

/** Linear sRGB channels; values outside 0..1 mean the colour is out of the sRGB gamut. */
export function oklchToLinearSrgb(value) {
  const [L, C, h] = parseOklch(value);
  const a = C * Math.cos((h * Math.PI) / 180);
  const b = C * Math.sin((h * Math.PI) / 180);
  const l = (L + 0.3963377774 * a + 0.2158037573 * b) ** 3;
  const m = (L - 0.1055613458 * a - 0.0638541728 * b) ** 3;
  const s = (L - 0.0894841775 * a - 1.291485548 * b) ** 3;
  return [
    4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s,
    -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s,
    -0.0041960863 * l - 0.7034186147 * m + 1.707614701 * s,
  ];
}

export const inGamut = (value, epsilon = 1e-3) =>
  oklchToLinearSrgb(value).every((c) => c >= -epsilon && c <= 1 + epsilon);

const clamp = (c) => Math.min(1, Math.max(0, c));

/** WCAG relative luminance; linear sRGB is what the WCAG formula linearises to. */
export function luminance(value) {
  const [r, g, b] = oklchToLinearSrgb(value).map(clamp);
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function contrast(fg, bg) {
  const [hi, lo] = [luminance(fg), luminance(bg)].sort((x, y) => y - x);
  return (hi + 0.05) / (lo + 0.05);
}

export function toHex(value) {
  const encode = (c) => {
    const v = clamp(c);
    const s = v <= 0.0031308 ? 12.92 * v : 1.055 * v ** (1 / 2.4) - 0.055;
    return Math.round(s * 255).toString(16).padStart(2, "0");
  };
  return `#${oklchToLinearSrgb(value).map(encode).join("")}`;
}

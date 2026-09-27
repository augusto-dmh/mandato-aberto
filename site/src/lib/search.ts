/** Home search and filters: pure functions shared by the static render and the Vue island. */
import { byName, sortKey } from "./format";

/** The trimmed record the home ships to the browser: no indicator value (grilling, "Home"). */
export interface DeputyCard {
  id: number;
  name: string;
  party: string;
  uf: string;
  inExercise: boolean;
  candidate: boolean;
  photo: string | null;
}

export interface Filters {
  query: string;
  uf: string;
  party: string;
  inExercise: boolean;
  candidacy: boolean;
}

export const initialFilters = (): Filters => ({ query: "", uf: "", party: "", inExercise: true, candidacy: false });

/** Clearing returns every filter to its initial state, "Em exercício" included. */
export const clearFilters = initialFilters;

export const sortByName = <T extends { name: string }>(deputies: T[]) => [...deputies].sort(byName);

export const matchesQuery = (name: string, query: string) => sortKey(name).includes(sortKey(query.trim()));

export function filterDeputies<T extends DeputyCard>(deputies: T[], filters: Filters): T[] {
  return deputies.filter(
    (d) =>
      matchesQuery(d.name, filters.query) &&
      (!filters.uf || d.uf === filters.uf) &&
      (!filters.party || d.party === filters.party) &&
      (!filters.inExercise || d.inExercise) &&
      (!filters.candidacy || d.candidate),
  );
}

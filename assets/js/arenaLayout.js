/**
 * Geometrie des Saalplans.
 *
 * Die Platznummern müssen exakt denen entsprechen, die die Auswahlfelder und
 * die Datenbank benutzen -- sonst zeigt der Plan einen anderen Platz an, als
 * bestellt wird. Die Rechnung stammt deshalb unverändert aus dem alten
 * Konva-Plan (SeatingPlanRow.vue):
 *
 *   rtl: label = (seats - i) + 1 - |{s aus skip : s >= i}|
 *   ltl: label = i - |{s aus skip : s <= i}|
 *
 * i ist die 1-basierte Rasterposition, skip sind die Gangpositionen. Dadurch
 * laufen die Nummern immer lückenlos von 1 bis (seats - skip.length); die
 * Lücke im Raster ist rein optisch.
 */
import { config } from "./config.js";

/** Rasterweite in SVG-Einheiten. */
export const GRID_X = 20;
export const GRID_Y = 28;
export const SEAT_R = 8;

/**
 * @returns {{rowLabel: string, y: number, seats: {label: number, x: number}[]}[]}
 */
export function blockLayout(blockId) {
  const section = config.sections[blockId];
  if (!section) return [];

  const rtl = section.seat_label_direction !== "ltl";
  let entries = Object.entries(section.rows);

  // Bei diesen Blöcken zählt der Plan die Reihen von der Bande weg.
  if (section.row_direction === "down") entries = entries.reverse();

  return entries.map(([rowLabel, row], rowIndex) => {
    const skip = Array.isArray(row.skip) ? row.skip : [];
    const seats = [];

    for (let i = 1; i <= row.seats; i++) {
      if (skip.includes(i)) continue;

      const label = rtl
        ? row.seats - i + 1 - skip.filter((s) => s >= i).length
        : i - skip.filter((s) => s <= i).length;

      seats.push({
        label,
        x: (i - 1 + (row.grid_start || 0)) * GRID_X + GRID_X,
      });
    }

    return { rowLabel, y: rowIndex * GRID_Y + GRID_Y, seats };
  });
}

/** Bounding-Box eines Blocks in SVG-Einheiten. */
export function blockBounds(blockId) {
  const rows = blockLayout(blockId);

  if (!rows.length) return { width: 0, height: 0 };

  const maxX = Math.max(...rows.flatMap((r) => r.seats.map((s) => s.x)));

  return {
    width: maxX + GRID_X,
    height: rows.length * GRID_Y + GRID_Y,
  };
}

/** Alle Platznummern eines Blocks, nach Reihe. */
export function rowsOf(blockId) {
  return blockLayout(blockId).map((row) => row.rowLabel);
}

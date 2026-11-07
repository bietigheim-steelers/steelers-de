/**
 * Preisberechnung für die Dauerkarte.
 *
 * Die Tabelle selbst steht nicht mehr hier, sondern kommt über dk-config aus
 * App\SeasonTicket\PriceTable. Diese Datei spiegelt nur noch die Auswahllogik
 * von PriceTable::priceFor() -- beide müssen dasselbe Ergebnis liefern, sonst
 * weicht der angezeigte vom gespeicherten Preis ab.
 */
import { config, seatedGroups } from "./config.js";

/** Kategorien, die zum Preis von "ermaessigt" laufen. */
const normalizeCategory = (category) =>
  config.reducedCategories.includes(category) ? "ermaessigt" : category;

/** Welche Preisgruppe gilt für diese Auswahl? */
const groupFor = (area, block) => {
  if (area === "stehplatz") return config.standingGroup;
  if (area === "rollstuhl") return config.wheelchairGroup;
  if (!block) return null;

  // Blocklabels kommen teils als "Block C", teils als "C".
  return config.blockGroups[String(block).trim().slice(-1)] || null;
};

export const ticketPrice = (type, category, area, block) => {
  const table = config.prices[type];
  if (!table) return 0;

  const group = groupFor(area, block);
  if (!group) return 0;

  const cat = area === "rollstuhl" ? "rollstuhl" : normalizeCategory(category);
  if (!cat) return 0;

  return table[group]?.[cat] ?? 0;
};

/**
 * Nachkommastellen nur, wenn es welche gibt. Früher war das fest auf 0
 * gerundet -- die BASIC-Stehplatzpreise 129,74 wurden dadurch als "130 €"
 * angezeigt, gespeichert wurden 129.
 */
export const formatPrice = (price) => {
  const fractional = Math.round(price * 100) % 100 !== 0;

  return new Intl.NumberFormat("de-DE", {
    style: "currency",
    currency: "EUR",
    minimumFractionDigits: fractional ? 2 : 0,
    maximumFractionDigits: fractional ? 2 : 0,
  }).format(price);
};

export const ticketPriceFormatted = (type, category, area, block) =>
  formatPrice(ticketPrice(type, category, area, block));

/**
 * Günstigster Preis für die "ab …"-Labels.
 *
 * Welche Preisgruppen in Frage kommen, hängt am Bereich und an der Kategorie.
 * Vorher wurde dafür der Bereich mit einem Blockbuchstaben überladen
 * (area = "C", um Familienpreise auf C,I,K einzugrenzen) -- das war nötig,
 * weil fehlende Kategorien als 0 durchgingen und sonst "ab 0,00 €" erschien.
 */
export const getLowestPrice = (type, category = null, area = null) => {
  const table = config.prices[type];
  if (!table) return 0;

  let groups;

  if (config.familyCategories.includes(category)) {
    groups = [config.familyGroup];
  } else if (area === "stehplatz") {
    groups = [config.standingGroup];
  } else if (area === "rollstuhl") {
    groups = [config.wheelchairGroup];
  } else if (area === "sitzplatz") {
    groups = seatedGroups;
  } else {
    groups = Object.keys(table);
  }

  const cat = area === "rollstuhl" ? "rollstuhl" : normalizeCategory(category);

  const candidates = groups.flatMap((group) => {
    const categories = table[group];
    if (!categories) return [];

    // Ohne Kategorie über alle minimieren, sonst nur die eine -- und eine
    // Kategorie, die es in dieser Gruppe nicht gibt, zählt gar nicht mit
    // statt als 0.
    if (!cat) return Object.values(categories);

    return cat in categories ? [categories[cat]] : [];
  });

  return candidates.length ? Math.min(...candidates) : 0;
};

export const getLowestPriceFormatted = (type, category = null, area = null) =>
  formatPrice(getLowestPrice(type, category, area));

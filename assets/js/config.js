/**
 * Konfiguration des Dauerkarten-Formulars.
 *
 * Preise, Kategorien, Hallenplan, Verkaufsphasen und Feature-Schalter kommen
 * aus PHP (App\SeasonTicket\FormConfig) und stehen als
 * <script type="application/json" id="dk-config"> in der Seite. Dadurch gibt
 * es keine zweite Preistabelle im Frontend und keine Datumsgrenzen mehr, die
 * in einer Komponente vergammeln.
 */

function read() {
  const el = document.getElementById("dk-config");

  if (!el) {
    console.error(
      'Dauerkarten-Formular: <script id="dk-config"> fehlt in der Seite. ' +
        "Nutzt das Formular-Template noch season_ticket_config()?",
    );
    return null;
  }

  try {
    return JSON.parse(el.textContent);
  } catch (error) {
    console.error("Dauerkarten-Formular: dk-config ist kein gültiges JSON.", error);
    return null;
  }
}

/**
 * Leere Hülle, damit ein fehlendes dk-config nicht in einen TypeError läuft,
 * sondern in ein sichtbar leeres Formular.
 */
const FALLBACK = {
  season: "",
  previousSeason: "",
  types: [],
  areas: [],
  prices: {},
  blockGroups: {},
  standingGroup: "J",
  wheelchairGroup: "",
  familyGroup: "",
  familyCategories: [],
  seatsPerCategory: {},
  reducedCategories: [],
  categoryLabels: {},
  selectableCategories: [],
  sections: {},
  wheelchairBlocks: {},
  ticketForms: [],
  payment: { options: [], note: "" },
  salePhases: [],
  features: {},
  endpoints: {},
  holdMinutes: 15,
};

export const config = read() || FALLBACK;

/** Die Blöcke, die als Dauerkarte verkauft werden, in Planreihenfolge. */
export const seatedBlocks = Object.keys(config.sections);

/** Preisgruppen der Sitzplatzblöcke, ohne Dopplungen. */
export const seatedGroups = [...new Set(Object.values(config.blockGroups))];

export const isFamilyCategory = (category) =>
  config.familyCategories.includes(category);

/** Wie viele Plätze eine Kategorie belegt. */
export const seatsForCategory = (category) =>
  config.seatsPerCategory[category] || 1;

/**
 * Contaos Request-Token. Das Template hängt ihn als data-Attribut an
 * #formApp; die Routen prüfen ihn über _token_check.
 */
export const requestToken = () =>
  document.getElementById("formApp")?.dataset.requestToken || "";

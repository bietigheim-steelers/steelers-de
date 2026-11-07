/**
 * Der Zustand der Dauerkartenbestellung -- eine Quelle für alles.
 *
 * Ersetzt Vueforms Datenbaum und die Mischung aus flachen Daten
 * (form$.data.x) und Element-Pfaden (el$("ticket_seats.my_seat.seat_block")).
 * Es gibt genau ein Formular pro Seite, deshalb ist der Zustand ein Modul-
 * Singleton und kein echtes Composable mit eigener Instanz.
 */
import { computed, reactive, readonly, watch } from "vue";
import { config, isFamilyCategory, requestToken, seatsForCategory } from "./config.js";
import { holdConsumed, releaseHold } from "./seatHold.js";
import { ticketPrice } from "./TicketPriceCalculator.js";
import {
  AREA_DEPENDENT_FIELDS,
  NEVER_PERSIST,
  allFields,
  isVisible,
  steps,
  validateField,
  validateStep,
} from "./formSchema.js";

const STORAGE_KEY = `dk-order:${config.season}:v1`;
const HASH_PREFIX = "#schritt-";

const blankOrder = () =>
  Object.fromEntries(allFields.map((field) => [field.name, null]));

export const order = reactive(blankOrder());

export const ui = reactive({
  step: 0,
  /** Felder, die der Nutzer schon verlassen hat -- erst dann zeigen wir Fehler. */
  touched: {},
  errors: {},
  /** Eingaben aus einer früheren Sitzung wiederhergestellt? */
  restored: false,
  submitting: false,
  done: false,
  /** Fehler, der nicht an einem einzelnen Feld hängt. */
  formError: null,
  orderNumber: null,
});

/**
 * Hat ticket_form das EVENTIM-Konto erzwungen? Dann darf ein Wechsel zurück
 * auf "plastik" es auch wieder freigeben. Vorher blieb eventim="ja" stehen
 * und zwei Pflichtfelder mit ihm.
 */
let eventimForcedByForm = false;

/* ------------------------------------------------------------------ *
 * Abgeleitete Werte
 * ------------------------------------------------------------------ */

export const price = computed(() =>
  ticketPrice(
    order.ticket_type,
    order.ticket_category,
    order.ticket_area,
    order.seat_block,
  ),
);

/** Wie viele Plätze die Bestellung belegt (Familienkarten mehrere). */
export const seatCount = computed(() => seatsForCategory(order.ticket_category));

/** Die Nachbarplätze, die eine Familienkarte mitnimmt. */
export const additionalSeats = computed(() => {
  const base = parseInt(order.seat_seat, 10);
  if (!base || seatCount.value < 2) return [];

  return Array.from({ length: seatCount.value - 1 }, (_, i) => base + i + 1);
});

/** Blöcke, in denen Familienkarten möglich sind (Preisgruppe C,I,K). */
export const familyBlocks = Object.entries(config.blockGroups)
  .filter(([, group]) => group === config.familyGroup)
  .map(([block]) => block);

export const availableBlocks = computed(() => {
  const all = Object.keys(config.sections);
  return isFamilyCategory(order.ticket_category)
    ? all.filter((block) => familyBlocks.includes(block))
    : all;
});

export const availableCategories = computed(() => {
  if (order.ticket_area === "rollstuhl") return ["rollstuhl"];

  const base = [...config.selectableCategories];
  // Familienkarten gibt es nur auf Sitzplätzen.
  return order.ticket_area === "sitzplatz"
    ? [...base, ...config.familyCategories]
    : base;
});

export const availableTicketForms = computed(() => config.ticketForms);

export const rows = computed(() => {
  const section = config.sections[order.seat_block];
  return section ? Object.keys(section.rows) : [];
});

/** Echte Plätze einer Reihe: Rasterplätze minus Gangpositionen. */
export const seatsInRow = computed(() => {
  const section = config.sections[order.seat_block];
  const row = section?.rows?.[order.seat_row];
  if (!row) return [];

  const count = row.seats - (row.skip?.length || 0);
  return Array.from({ length: count }, (_, i) => i + 1);
});

export const isDirty = computed(() =>
  allFields.some((field) => {
    const value = order[field.name];
    return value !== null && value !== undefined && value !== "";
  }),
);

/* ------------------------------------------------------------------ *
 * Felder setzen und validieren
 * ------------------------------------------------------------------ */

function clearFields(names) {
  for (const name of names) {
    order[name] = null;
    delete ui.errors[name];
    delete ui.touched[name];
  }
}

export function setField(name, value) {
  order[name] = value;

  // Ein Feld, das schon einmal falsch war, prüfen wir bei jeder Änderung
  // weiter -- so verschwindet der Fehler, sobald er behoben ist. Noch nie
  // beanstandete Felder bleiben bis zum Verlassen in Ruhe.
  if (ui.errors[name]) {
    const message = validateField(name, order);
    if (message) ui.errors[name] = message;
    else delete ui.errors[name];
  }

  applyDependencies(name, value);
}

export function blurField(name) {
  ui.touched[name] = true;
  const message = validateField(name, order);
  if (message) ui.errors[name] = message;
  else delete ui.errors[name];
}

/**
 * Was eine Auswahl an anderen Feldern nach sich zieht.
 */
function applyDependencies(name, value) {
  if (name === "ticket_area") {
    // Vorher wurde hier nur ticket_category geleert -- die seat_*-Werte
    // blieben stehen und wurden beim Absenden mitgeschickt.
    clearFields(AREA_DEPENDENT_FIELDS);

    // Im Rollstuhlbereich gibt es nur eine Kategorie. Die fragen wir nicht ab.
    if (value === "rollstuhl") {
      order.ticket_category = "rollstuhl";
    }
  }

  if (name === "ticket_category") {
    // Familienkarten gibt es nur in C, I, K. Passt der gewählte Block nicht
    // mehr, Platzwahl zurücksetzen.
    if (order.seat_block && !availableBlocks.value.includes(order.seat_block)) {
      clearFields(["seat_block", "seat_row", "seat_seat"]);
    }
  }

  if (name === "seat_block") {
    clearFields(["seat_row", "seat_seat"]);
  }

  if (name === "seat_row") {
    clearFields(["seat_seat"]);
  }

  if (name === "ticket_form") {
    const needsEventim = value === "mobile" || value === "mobile_plastik";

    if (needsEventim) {
      eventimForcedByForm = order.eventim !== "ja";
      order.eventim = "ja";
    } else if (eventimForcedByForm) {
      // Zurückgenommen: das Konto war nur wegen der mobilen Karte Pflicht.
      eventimForcedByForm = false;
      order.eventim = null;
      clearFields(["eventim_email", "eventim_account"]);
    }
  }

  if (name === "eventim" && value !== "ja") {
    clearFields(["eventim_email", "eventim_account"]);
  }
}

/** Ist das EVENTIM-Konto gerade durch die Kartenform erzwungen? */
export const eventimLocked = computed(
  () => order.ticket_form === "mobile" || order.ticket_form === "mobile_plastik",
);

export const fieldVisible = (name) => {
  const field = allFields.find((f) => f.name === name);
  return field ? isVisible(field, order) : true;
};

/* ------------------------------------------------------------------ *
 * Schritte
 * ------------------------------------------------------------------ */

export const stepCount = steps.length;

export function checkStep(index = ui.step) {
  const found = validateStep(index, order);
  Object.assign(ui.errors, found);

  for (const name of Object.keys(found)) {
    ui.touched[name] = true;
  }

  return Object.keys(found);
}

export function goToStep(index, { push = true } = {}) {
  if (index < 0 || index >= stepCount) return;

  ui.step = index;
  ui.formError = null;

  if (push && typeof window !== "undefined") {
    const hash = `${HASH_PREFIX}${index + 1}`;
    if (window.location.hash !== hash) {
      window.history.pushState({ step: index }, "", stepUrl(hash));
    }
  }
}

export function nextStep() {
  const failed = checkStep();

  if (failed.length) return failed;

  goToStep(ui.step + 1);
  return [];
}

export function previousStep() {
  goToStep(ui.step - 1);
}

/**
 * Die URL muss vollständig sein. Das Layout gibt ein <base href> aus, und
 * history.pushState löst relative Adressen gegen die Basis auf -- ein blosses
 * "#schritt-2" würde den Seitenpfad wegwerfen.
 */
function stepUrl(hash) {
  return `${window.location.pathname}${window.location.search}${hash}`;
}

function stepFromHash() {
  const match = /^#schritt-(\d+)$/.exec(window.location.hash || "");
  if (!match) return 0;

  const index = parseInt(match[1], 10) - 1;
  return index >= 0 && index < stepCount ? index : 0;
}

/* ------------------------------------------------------------------ *
 * Persistenz
 * ------------------------------------------------------------------ */

function persist() {
  if (typeof sessionStorage === "undefined" || ui.done) return;

  const payload = {};
  for (const field of allFields) {
    if (NEVER_PERSIST.includes(field.name)) continue;
    if (order[field.name] !== null && order[field.name] !== "") {
      payload[field.name] = order[field.name];
    }
  }

  try {
    if (Object.keys(payload).length) {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ step: ui.step, order: payload }));
    } else {
      sessionStorage.removeItem(STORAGE_KEY);
    }
  } catch {
    // Privater Modus o. ä. -- Persistenz ist Komfort, kein Muss.
  }
}

export function forgetStored() {
  try {
    sessionStorage?.removeItem(STORAGE_KEY);
  } catch {
    /* egal */
  }
}

/**
 * Wiederherstellen passiert nicht still: ui.restored steuert einen sichtbaren
 * Hinweis mit "Neu anfangen". Stille Wiederherstellung veralteter Daten ist
 * schlimmer als Datenverlust.
 */
function restore() {
  if (typeof sessionStorage === "undefined") return;

  let stored;
  try {
    stored = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || "null");
  } catch {
    return;
  }

  if (!stored?.order || !Object.keys(stored.order).length) return;

  Object.assign(order, stored.order);
  ui.restored = true;
}

export function resetAll({ keepStep = false } = {}) {
  // Gehaltene Plätze sofort für andere freigeben, nicht erst nach 15 Minuten.
  releaseHold();
  Object.assign(order, blankOrder());
  ui.errors = {};
  ui.touched = {};
  ui.restored = false;
  ui.formError = null;
  ui.done = false;
  ui.orderNumber = null;
  eventimForcedByForm = false;
  forgetStored();

  if (!keepStep) goToStep(0);
}

/* ------------------------------------------------------------------ *
 * Absenden
 * ------------------------------------------------------------------ */

const SERVER_MESSAGES = {
  seat_already_booked:
    "Dieser Platz ist inzwischen vergeben. Bitte wähle einen anderen Platz.",
  seat_non_existent: "Diesen Platz gibt es nicht. Bitte wähle einen anderen.",
  seat_unavailable:
    "In diesem Bereich ist gerade kein Platz mehr frei. Bitte wähle einen anderen Bereich.",
  too_many_requests:
    "Da kamen sehr viele Anfragen von dir. Bitte warte einen Moment und versuch es dann erneut.",
  invalid_email: "Die E-Mail-Adresse wird vom Server nicht akzeptiert.",
};

export async function submit() {
  // Alle Schritte prüfen, nicht nur den letzten.
  for (let i = 0; i < stepCount; i++) {
    const failed = checkStep(i);
    if (failed.length) {
      goToStep(i);
      return { ok: false, firstInvalid: failed[0] };
    }
  }

  if (ui.submitting) return { ok: false };

  ui.submitting = true;
  ui.formError = null;

  const body = new FormData();
  body.append("REQUEST_TOKEN", requestToken());

  for (const field of allFields) {
    if (!isVisible(field, order)) continue;
    const value = order[field.name];
    if (value === null || value === undefined || value === "") continue;
    body.append(field.name, typeof value === "boolean" ? (value ? "1" : "") : value);
  }

  try {
    const response = await fetch(config.endpoints.order, {
      method: "POST",
      body,
      headers: { Accept: "text/plain" },
    });

    const text = (await response.text()).trim();

    if (response.ok) {
      ui.done = true;
      // Der gehaltene Platz ist jetzt gebucht -- Uhr aus, nicht freigeben.
      holdConsumed();
      forgetStored();
      return { ok: true };
    }

    const key = text.startsWith("missing_field:") ? "missing_field" : text;

    ui.formError =
      SERVER_MESSAGES[key] ||
      "Da ist etwas schiefgelaufen. Bitte prüfe deine Angaben – besonders die " +
        "E-Mail-Adresse – und versuch es erneut. Klappt es weiter nicht, melde " +
        "dich bitte unter ticketing@steelers.de.";

    return { ok: false, reason: text };
  } catch {
    ui.formError =
      "Die Verbindung zum Server ist abgebrochen. Bitte versuch es noch einmal.";
    return { ok: false, reason: "network" };
  } finally {
    ui.submitting = false;
  }
}

/* ------------------------------------------------------------------ *
 * Einmalige Einrichtung
 * ------------------------------------------------------------------ */

let initialised = false;

export function initOrder() {
  if (initialised || typeof window === "undefined") return;
  initialised = true;

  restore();
  ui.step = stepFromHash();

  window.addEventListener("popstate", () => {
    ui.step = stepFromHash();
  });

  // Browser-Zurück soll einen Schritt zurückgehen, nicht aus dem Formular
  // heraus. Dafür muss der erste Schritt auch einen History-Eintrag haben.
  if (!window.location.hash.startsWith(HASH_PREFIX)) {
    window.history.replaceState(
      { step: ui.step },
      "",
      stepUrl(`${HASH_PREFIX}${ui.step + 1}`),
    );
  }

  watch(order, persist, { deep: true });
  watch(() => ui.step, persist);

  window.addEventListener("beforeunload", (event) => {
    if (!ui.done && isDirty.value) {
      event.preventDefault();
      event.returnValue = "";
    }
  });
}

export const orderReadonly = readonly(order);

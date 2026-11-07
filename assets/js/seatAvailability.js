/**
 * Belegung der Sitzplätze -- live statt einmal pro Seitenaufruf.
 *
 * Ersetzt SeatingPlanLoad.js, das den Stand in einem Modul-Singleton cachte
 * und ihn für die gesamte Lebensdauer der Seite behielt. Wer zehn Minuten im
 * Formular war, sah Plätze als frei, die längst vergeben waren -- und erfuhr
 * das erst beim Absenden.
 */
import { reactive } from "vue";
import { config } from "./config.js";

export const availability = reactive({
  /** Verkauft. */
  booked: new Set(),
  /** Vorbehalt für den bisherigen Dauerkarteninhaber. */
  reserved: new Set(),
  /** Gerade von jemand anderem im Bestellvorgang gehalten. */
  held: new Set(),
  loading: false,
  loadedAt: 0,
  failed: false,
});

export const seatId = (block, row, seat) => `${block}_${row}_${seat}`;

/** Belegt: kann nicht bestellt werden. */
export const isTaken = (block, row, seat) => {
  const id = seatId(block, row, seat);
  return availability.booked.has(id) || availability.held.has(id);
};

/** Vorbehalten: bestellbar, aber mit Hinweis. */
export const isReserved = (block, row, seat) =>
  availability.reserved.has(seatId(block, row, seat));

/**
 * Übernimmt eine Belegung, die eine andere Antwort schon mitgeliefert hat
 * (hold und release geben sie zurück) -- spart einen zweiten Request.
 */
export function applyAvailability(data) {
  availability.booked = new Set(data.booked || []);
  availability.reserved = new Set(data.reserved || []);
  availability.held = new Set(data.held || []);
  availability.loadedAt = Date.now();
  availability.failed = false;
}

export async function refresh() {
  if (availability.loading) return;

  availability.loading = true;

  try {
    const response = await fetch(`${config.endpoints.seats}?t=${Date.now()}`, {
      headers: { Accept: "application/json" },
      cache: "no-store",
    });

    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    applyAvailability(await response.json());
  } catch (error) {
    // Kein harter Fehler: der Server prüft beim Halten und beim Bestellen
    // ohnehin noch einmal. Nur der Plan ist dann womöglich veraltet.
    availability.failed = true;
    console.warn("Sitzplatzbelegung konnte nicht geladen werden.", error);
  } finally {
    availability.loading = false;
  }
}

let timer = null;

export function startPolling(intervalMs = 20000) {
  stopPolling();
  refresh();

  timer = setInterval(() => {
    // Im Hintergrundtab nicht pollen -- beim Zurückkommen holen wir nach.
    if (document.visibilityState === "visible") refresh();
  }, intervalMs);

  document.addEventListener("visibilitychange", onVisible);
}

function onVisible() {
  if (document.visibilityState === "visible") refresh();
}

export function stopPolling() {
  if (timer) clearInterval(timer);
  timer = null;
  document.removeEventListener("visibilitychange", onVisible);
}

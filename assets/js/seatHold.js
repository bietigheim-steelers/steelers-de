/**
 * Befristete Platzreservierung mit Countdown.
 *
 * Der eigentliche Hold liegt auf dem Server; wer ihn hält, steht in einem
 * HttpOnly-Cookie, das dieser Code weder sieht noch braucht. Hier läuft nur
 * die Uhr und der Zustand für die Anzeige.
 */
import { reactive } from "vue";
import { config, requestToken } from "./config.js";
import { applyAvailability } from "./seatAvailability.js";

/** Unter diesem Rest wird im Hintergrund verlängert. */
const EXTEND_BELOW_SECONDS = 120;

export const hold = reactive({
  /** Halten wir gerade Plätze? */
  active: false,
  /** Unix-Zeit, bis zu der gehalten wird. */
  until: 0,
  secondsLeft: 0,
  seats: 0,
  pending: false,
  /** Abgelaufen, ohne dass bestellt wurde. */
  expired: false,
  /** Fehlerschlüssel vom Server, z. B. seat_on_hold. */
  error: null,
  /** Was wir zuletzt gehalten haben -- für das Verlängern. */
  seat: null,
});

export const HOLD_MESSAGES = {
  seat_on_hold:
    "Diesen Platz sucht sich gerade jemand anderes aus. Bitte wähle einen anderen.",
  seat_already_booked: "Dieser Platz ist inzwischen vergeben.",
  seat_non_existent: "Diesen Platz gibt es nicht.",
  invalid_seat: "Diese Platzangabe können wir nicht verarbeiten.",
  too_many_requests:
    "Du hast sehr schnell sehr viele Plätze ausprobiert. Bitte warte einen Moment.",
  network: "Der Platz konnte gerade nicht reserviert werden.",
};

export const holdMessage = () => (hold.error ? HOLD_MESSAGES[hold.error] || HOLD_MESSAGES.network : "");

/** mm:ss */
export const formatCountdown = (seconds) => {
  const total = Math.max(0, seconds);
  const m = Math.floor(total / 60);
  const s = total % 60;
  return `${m}:${String(s).padStart(2, "0")}`;
};

let timer = null;

function startClock() {
  stopClock();

  timer = setInterval(() => {
    hold.secondsLeft = Math.max(0, Math.round((hold.until * 1000 - Date.now()) / 1000));

    if (hold.secondsLeft === 0) {
      hold.active = false;
      hold.expired = true;
      stopClock();
      return;
    }

    // Wer noch im Formular sitzt, soll den Platz nicht verlieren. Wir
    // verlängern still, statt den Countdown ablaufen zu lassen.
    if (hold.secondsLeft < EXTEND_BELOW_SECONDS && !hold.pending && hold.seat) {
      requestHold(hold.seat, { silent: true });
    }
  }, 1000);
}

function stopClock() {
  if (timer) clearInterval(timer);
  timer = null;
}

/**
 * Hält den Platz. Ein neuer Aufruf gibt die vorher gehaltenen Plätze
 * serverseitig automatisch frei -- niemand sammelt beim Umwählen die halbe
 * Reihe ein.
 */
export async function requestHold(seat, { silent = false } = {}) {
  if (!seat?.block || !seat?.row || !seat?.seat) return false;

  hold.pending = true;
  if (!silent) hold.error = null;

  const body = new FormData();
  body.append("REQUEST_TOKEN", requestToken());
  body.append("seat_block", seat.block);
  body.append("seat_row", seat.row);
  body.append("seat_seat", seat.seat);
  body.append("ticket_category", seat.category || "");

  try {
    const response = await fetch(config.endpoints.hold, { method: "POST", body });
    const data = await response.json().catch(() => ({}));

    if (data.availability) applyAvailability(data.availability);

    if (!response.ok) {
      hold.error = data.error || "network";
      hold.active = false;
      hold.expired = false;
      stopClock();
      return false;
    }

    hold.until = data.until;
    hold.secondsLeft = data.seconds ?? 0;
    hold.seats = data.seats ?? 1;
    hold.seat = { ...seat };
    hold.active = true;
    hold.expired = false;
    hold.error = null;
    startClock();

    return true;
  } catch {
    hold.error = "network";
    return false;
  } finally {
    hold.pending = false;
  }
}

export async function releaseHold() {
  stopClock();

  const wasActive = hold.active || hold.expired;

  hold.active = false;
  hold.expired = false;
  hold.until = 0;
  hold.secondsLeft = 0;
  hold.seat = null;
  hold.error = null;

  if (!wasActive) return;

  const body = new FormData();
  body.append("REQUEST_TOKEN", requestToken());

  try {
    const response = await fetch(config.endpoints.release, { method: "POST", body });
    const data = await response.json().catch(() => ({}));
    if (data.availability) applyAvailability(data.availability);
  } catch {
    // Nicht schlimm: der Hold läuft von selbst ab.
  }
}

/** Nach dem Absenden gehört der Platz endgültig uns -- Uhr aus. */
export function holdConsumed() {
  stopClock();
  hold.active = false;
  hold.expired = false;
  hold.secondsLeft = 0;
}

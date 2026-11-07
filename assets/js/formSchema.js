/**
 * Alle Felder des Bestellformulars, ihre Pflichtregeln und die deutschen
 * Meldungen -- an einer Stelle.
 *
 * Vorher waren das 24 `rules="required"`-Attribute, verteilt über fünf
 * Komponenten, plus Bedingungen in Form von `:conditions`-Arrays. Hier steht
 * dasselbe als Daten: welche Felder zu welchem Schritt gehören, wann ein Feld
 * Pflicht ist und was im Fehlerfall dasteht.
 *
 * Bewusst ohne Schema-Bibliothek: die Regeln sind "Pflichtfeld", "E-Mail" und
 * ein paar Bedingungen. Dafür lohnt keine Abhängigkeit, und das Projekt hat
 * kein TypeScript, wo sich die Typinferenz solcher Bibliotheken auszahlen
 * würde.
 */
import { config } from "./config.js";

const filled = (value) =>
  value !== null && value !== undefined && String(value).trim() !== "";

const rules = {
  email: {
    test: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(value).trim()),
    message: "Bitte gib eine gültige E-Mail-Adresse ein.",
  },
  plz: {
    test: (value) => /^\d{4,5}$/.test(String(value).trim()),
    message: "Bitte gib eine gültige Postleitzahl ein.",
  },
  digits: {
    test: (value) => /^\d+$/.test(String(value).trim()),
    message: "Bitte gib nur Ziffern ein.",
  },
};

/** Bedingungen, die ein Feld überhaupt erst sichtbar machen. */
const when = {
  seated: (o) => o.ticket_area === "sitzplatz",
  wheelchair: (o) => o.ticket_area === "rollstuhl",
  member: (o) => o.ticket_category === "mitglied",
  eventim: (o) => o.eventim === "ja",
};

/**
 * Die vier Schritte. `fields` listet jedes Feld mit:
 *   name      Schlüssel im Bestelldatensatz und im POST
 *   label     für Fehlermeldungen und die Zusammenfassung
 *   required  true, false oder eine Funktion über der Bestellung
 *   visible   optional: Funktion, die entscheidet, ob das Feld zählt
 *   rule      optional: zusätzliche Formatprüfung
 */
export const steps = [
  {
    name: "dauerkarte",
    label: "Meine Dauerkarte",
    fields: [
      { name: "ticket_type", label: "Dauerkarte", required: true },
      { name: "ticket_area", label: "Bereich", required: true },
      { name: "ticket_category", label: "Kategorie", required: true },
      { name: "seat_block", label: "Block", required: true, visible: when.seated },
      { name: "seat_row", label: "Reihe", required: true, visible: when.seated },
      { name: "seat_seat", label: "Platz", required: true, visible: when.seated },
      {
        name: "seat_rollstuhl_block",
        label: "Rollstuhlblock",
        required: true,
        visible: when.wheelchair,
      },
      { name: "ticket_form", label: "Dauerkartenform", required: true },
    ],
  },
  {
    name: "bezahlung",
    label: "Bezahlung",
    fields: [{ name: "ticket_payment", label: "Bezahlung", required: true }],
  },
  {
    name: "kontakt",
    label: "Meine Kontaktdaten",
    fields: [
      // Pflicht, weil der Vorname im Betreff der Bestellmail steht.
      { name: "customer_firstname", label: "Vorname", required: true, autocomplete: "given-name" },
      { name: "customer_name", label: "Nachname", required: true, autocomplete: "family-name" },
      {
        name: "customer_street",
        label: "Straße und Hausnummer",
        required: true,
        autocomplete: "street-address",
      },
      {
        name: "customer_plz",
        label: "PLZ",
        required: true,
        rule: "plz",
        autocomplete: "postal-code",
        inputmode: "numeric",
      },
      { name: "customer_city", label: "Ort", required: true, autocomplete: "address-level2" },
      {
        name: "customer_phone",
        label: "Telefon",
        required: false,
        autocomplete: "tel",
        inputmode: "tel",
      },
      {
        name: "customer_email",
        label: "E-Mail",
        required: true,
        rule: "email",
        autocomplete: "email",
        inputmode: "email",
      },
      {
        name: "customer_birthday",
        label: "Geburtstag",
        required: false,
        autocomplete: "bday",
      },
      {
        name: "customer_member",
        label: "Mitgliedsnummer",
        required: true,
        visible: when.member,
        inputmode: "numeric",
      },
      { name: "customer_last_season", label: "Dauerkarte im Vorjahr", required: true },
      { name: "eventim", label: "EVENTIM-Konto", required: true },
      {
        name: "eventim_email",
        label: "EVENTIM E-Mail",
        required: true,
        rule: "email",
        visible: when.eventim,
        inputmode: "email",
      },
      {
        name: "eventim_account",
        label: "EVENTIM Kundennummer",
        required: true,
        rule: "digits",
        visible: when.eventim,
        inputmode: "numeric",
      },
    ],
  },
  {
    name: "uebersicht",
    label: "Übersicht",
    fields: [
      { name: "bemerkung", label: "Bemerkung", required: false },
      { name: "terms", label: "AGB", required: true },
      { name: "data_privacy", label: "Datenschutz", required: true },
    ],
  },
];

export const allFields = steps.flatMap((step) => step.fields);

export const fieldByName = Object.fromEntries(
  allFields.map((field) => [field.name, field]),
);

/** Zählt ein Feld bei der aktuellen Auswahl überhaupt? */
export const isVisible = (field, order) =>
  typeof field.visible === "function" ? field.visible(order) : true;

const isRequired = (field, order) =>
  typeof field.required === "function" ? field.required(order) : !!field.required;

/**
 * Prüft ein einzelnes Feld. Gibt die Meldung zurück oder null.
 */
export function validateField(name, order) {
  const field = fieldByName[name];
  if (!field || !isVisible(field, order)) return null;

  const value = order[name];

  if (isRequired(field, order) && !filled(value)) {
    return `Bitte ${field.label} ausfüllen.`;
  }

  if (filled(value) && field.rule && !rules[field.rule].test(value)) {
    return rules[field.rule].message;
  }

  return null;
}

/**
 * Prüft einen ganzen Schritt. Gibt { name: meldung } für alle Fehler zurück.
 */
export function validateStep(stepIndex, order) {
  const step = steps[stepIndex];
  if (!step) return {};

  const found = {};

  for (const field of step.fields) {
    const message = validateField(field.name, order);
    if (message) found[field.name] = message;
  }

  return found;
}

/** Felder, die nie in den sessionStorage gehören. */
export const NEVER_PERSIST = ["terms", "data_privacy"];

/** Die Felder, die beim Wechsel des Bereichs ihren Sinn verlieren. */
export const AREA_DEPENDENT_FIELDS = [
  "ticket_category",
  "seat_block",
  "seat_row",
  "seat_seat",
  "seat_rollstuhl_block",
];

export const seasonLabel = () => config.season;

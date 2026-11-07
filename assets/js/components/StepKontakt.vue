<template>
  <div>
    <h3 class="text-lg font-medium leading-tight text-gray-900">Deine Kontaktdaten</h3>
    <p class="mt-1 text-sm text-gray-500">
      Dein Browser kann die Adressfelder in einem Rutsch ausfüllen.
    </p>

    <div class="grid gap-x-6 sm:grid-cols-2">
      <FormField name="customer_firstname" label="Vorname" />
      <FormField name="customer_name" label="Nachname" />
    </div>

    <FormField name="customer_street" label="Straße und Hausnummer" />

    <div class="grid gap-x-6 sm:grid-cols-3">
      <FormField name="customer_plz" label="PLZ" />
      <div class="sm:col-span-2">
        <FormField name="customer_city" label="Ort" />
      </div>
    </div>

    <div class="grid gap-x-6 sm:grid-cols-2">
      <FormField name="customer_email" label="E-Mail" type="email" />
      <FormField name="customer_phone" label="Telefon" type="tel" />
    </div>

    <FormField name="customer_birthday" label="Geburtstag" type="date" />

    <FormField
      name="customer_member"
      label="Mitgliedsnummer (SC/PURE)"
      hint="Steht auf deinem Mitgliedsausweis."
    />

    <FormField
      name="customer_last_season"
      as="tabs"
      :label="`Ich hatte in der Saison ${config.previousSeason} bereits eine Dauerkarte`"
      :options="jaNein"
    />

    <FormField
      name="eventim"
      as="tabs"
      label="Ich habe ein EVENTIM-Konto"
      :options="jaNein"
      :disabled="eventimLocked"
      :hint="eventimHint"
    />

    <div class="grid gap-x-6 sm:grid-cols-2">
      <FormField
        name="eventim_email"
        label="E-Mail deines EVENTIM-Kontos"
        type="email"
      />
      <FormField
        name="eventim_account"
        label="EVENTIM Kundennummer"
        :hint="`Die Kundennummer steht auch auf der Rückseite deiner Dauerkarte ${config.previousSeason}.`"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { config } from "../config.js";
import { eventimLocked } from "../useOrder.js";
import FormField from "./FormField.vue";

const jaNein = [
  { value: "ja", label: "Ja" },
  { value: "nein", label: "Nein" },
];

const eventimHint = computed(() =>
  eventimLocked.value
    ? "Für die mobile Dauerkarte wird ein EVENTIM-Konto gebraucht – deshalb ist das hier gesetzt."
    : "",
);
</script>

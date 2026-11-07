<template>
  <div>
    <h3 class="text-lg font-medium leading-tight text-gray-900">
      Deine Bestellung
    </h3>

    <dl class="mt-4 divide-y divide-black/10 text-sm">
      <div v-for="row in summary" :key="row.label" class="flex gap-4 py-3">
        <dt class="w-44 shrink-0 text-gray-500">{{ row.label }}</dt>
        <dd class="flex-1 font-medium text-gray-900" v-html="row.value" />
        <button
          v-if="row.step !== undefined"
          type="button"
          class="shrink-0 text-sm font-medium text-steelgreen underline underline-offset-2 transition-transform duration-150 ease-out active:scale-[0.97] motion-reduce:transition-none motion-reduce:active:scale-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-steelblue"
          @click="goToStep(row.step)"
        >
          Ändern
        </button>
      </div>

      <div class="flex items-baseline gap-4 py-4">
        <dt class="w-44 shrink-0 text-gray-500">Preis</dt>
        <dd class="flex-1 text-xl font-semibold text-steelgreen">
          {{ formatPrice(price) }}
        </dd>
      </div>
    </dl>

    <FormField
      name="bemerkung"
      as="textarea"
      label="Bemerkung zu deiner Bestellung"
      placeholder="Optional – zum Beispiel ein Wunsch zur Platzierung"
    />

    <div class="mt-8 space-y-4 border-t border-black/10 pt-6">
      <FormField
        name="terms"
        as="checkbox"
        :label="`Ich akzeptiere die <a class='underline' href='/agb' target='_blank' rel='noopener'>AGB</a> sowie die Konditionen und Bedingungen zur Dauerkarte ${config.season}.`"
      />
      <FormField
        name="data_privacy"
        as="checkbox"
        label="Ich habe die <a class='underline' href='/datenschutz' target='_blank' rel='noopener'>Datenschutzerklärung</a> zur Kenntnis genommen."
      />
    </div>
  </div>
</template>

<script setup>
/**
 * Die Zusammenfassung zeigt jetzt auch Zahlungsart und Kartenform -- bei
 * einer verbindlichen Bestellung gehören sie dazu und fehlten vorher.
 */
import { computed } from "vue";
import { config } from "../config.js";
import { formatPrice } from "../TicketPriceCalculator.js";
import { goToStep, order, price, seatCount } from "../useOrder.js";
import FormField from "./FormField.vue";

const labelOf = (list, value) =>
  list.find((entry) => entry.value === value)?.label || "–";

const seatText = computed(() => {
  if (order.ticket_area === "stehplatz") return "Stehplatz (EgeTrans Block)";

  if (order.ticket_area === "rollstuhl") {
    return order.seat_rollstuhl_block || "–";
  }

  if (!order.seat_block) return "–";

  const base = Number(order.seat_seat);
  const seats =
    seatCount.value > 1
      ? `Plätze ${base}–${base + seatCount.value - 1}`
      : `Platz ${base}`;

  return `Block ${order.seat_block}, Reihe ${order.seat_row}, ${seats}`;
});

const summary = computed(() => [
  {
    label: "Dauerkarte",
    value: `${labelOf(config.types, order.ticket_type)} ${config.season}`,
    step: 0,
  },
  { label: "Platz", value: seatText.value, step: 0 },
  {
    label: "Kategorie",
    value: config.categoryLabels[order.ticket_category] || "–",
    step: 0,
  },
  { label: "Kartenform", value: labelOf(config.ticketForms, order.ticket_form), step: 0 },
  {
    label: "Bezahlung",
    value: labelOf(config.payment.options, order.ticket_payment),
    step: 1,
  },
  {
    label: "Name",
    value: [order.customer_firstname, order.customer_name].filter(Boolean).join(" ") || "–",
    step: 2,
  },
  {
    label: "Adresse",
    value:
      [order.customer_street, [order.customer_plz, order.customer_city].filter(Boolean).join(" ")]
        .filter(Boolean)
        .join("<br>") || "–",
    step: 2,
  },
  { label: "E-Mail", value: order.customer_email || "–", step: 2 },
]);
</script>

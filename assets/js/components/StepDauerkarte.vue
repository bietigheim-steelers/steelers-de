<template>
  <div>
    <AccordionQuestion
      v-for="(question, index) in questions"
      :key="question.key"
      :title="question.title"
      :summary="question.summary"
      :answered="question.answered"
      :open="openKey === question.key"
      :reachable="index === 0 || questions[index - 1].answered"
      @open="openKey = question.key"
    >
      <!-- Welche Dauerkarte -->
      <template v-if="question.key === 'type'">
        <FormField name="ticket_type" as="cards" label="" :options="typeOptions" bare />
      </template>

      <!-- Bereich -->
      <template v-else-if="question.key === 'area'">
        <FormField name="ticket_area" as="cards" label="" :options="areaOptions" bare />
      </template>

      <!-- Kategorie -->
      <template v-else-if="question.key === 'category'">
        <FormField
          name="ticket_category"
          as="cards"
          label=""
          :options="categoryOptions"
          bare
        />
      </template>

      <!-- Sitzplatz -->
      <template v-else-if="question.key === 'seat'">
        <SeatPicker />
      </template>

      <!-- Rollstuhlplatz -->
      <template v-else-if="question.key === 'wheelchair'">
        <FormField
          name="seat_rollstuhl_block"
          as="cards"
          label=""
          :options="wheelchairOptions"
          bare
        />
      </template>

      <!-- Kartenform -->
      <template v-else-if="question.key === 'form'">
        <FormField
          name="ticket_form"
          as="cards"
          label=""
          :options="formOptions"
          :hint="formHint"
          bare
        />
      </template>
    </AccordionQuestion>
  </div>
</template>

<script setup>
/**
 * Schritt 1 als geführter Konfigurator.
 *
 * Vorher standen hier acht Elementgruppen gleichzeitig untereinander -- die
 * komplette Produktkonfiguration in einem Scroll. Jetzt ist immer genau eine
 * Frage offen, beantwortete fallen auf eine Zeile zusammen.
 */
import { computed, ref, watch } from "vue";
import { config } from "../config.js";
import { getLowestPriceFormatted } from "../TicketPriceCalculator.js";
import { availableCategories, order, seatCount } from "../useOrder.js";
import AccordionQuestion from "./AccordionQuestion.vue";
import FormField from "./FormField.vue";
import SeatPicker from "./SeatPicker.vue";

const openKey = ref("type");

const labelOf = (list, value) =>
  list.find((entry) => entry.value === value)?.label || "";

/* --- Optionen ---------------------------------------------------------- */

const typeOptions = computed(() =>
  config.types.map((type) => ({
    ...type,
    note: `ab ${getLowestPriceFormatted(type.value)}`,
  })),
);

const areaOptions = computed(() =>
  config.areas.map((area) => ({
    ...area,
    note: `ab ${getLowestPriceFormatted(order.ticket_type, null, area.value)}`,
  })),
);

const categoryOptions = computed(() =>
  availableCategories.value.map((category) => ({
    value: category,
    label: config.categoryLabels[category] || category,
    note: getLowestPriceFormatted(order.ticket_type, category, order.ticket_area),
  })),
);

const wheelchairOptions = computed(() =>
  Object.keys(config.wheelchairBlocks).map((block) => ({
    value: `Block ${block}`,
    label: `Block ${block}`,
  })),
);

const formOptions = computed(() => config.ticketForms);

const formHint = computed(() =>
  order.ticket_form === "mobile" || order.ticket_form === "mobile_plastik"
    ? "Für die mobile Dauerkarte brauchst du ein EVENTIM-Konto – danach fragen wir im Schritt „Meine Kontaktdaten“."
    : "",
);

/* --- Fragen ------------------------------------------------------------ */

const seatAnswered = computed(
  () => !!(order.seat_block && order.seat_row && order.seat_seat),
);

const seatSummary = computed(() => {
  if (!seatAnswered.value) return "";

  const base = `Block ${order.seat_block}, Reihe ${order.seat_row}, Platz ${order.seat_seat}`;
  return seatCount.value > 1 ? `${base} (+${seatCount.value - 1} weitere)` : base;
});

const questions = computed(() => {
  const list = [
    {
      key: "type",
      title: `Welche Dauerkarte ${config.season} möchtest du?`,
      answered: !!order.ticket_type,
      summary: labelOf(config.types, order.ticket_type),
    },
    {
      key: "area",
      title: "In welchem Bereich?",
      answered: !!order.ticket_area,
      summary: labelOf(config.areas, order.ticket_area),
    },
  ];

  // Im Rollstuhlbereich gibt es nur eine Kategorie, die setzt useOrder selbst.
  if (order.ticket_area !== "rollstuhl") {
    list.push({
      key: "category",
      title: "Welche Kategorie trifft auf dich zu?",
      answered: !!order.ticket_category,
      summary: config.categoryLabels[order.ticket_category] || "",
    });
  }

  if (order.ticket_area === "sitzplatz") {
    list.push({
      key: "seat",
      title: "Welcher Platz soll es sein?",
      answered: seatAnswered.value,
      summary: seatSummary.value,
    });
  }

  if (order.ticket_area === "rollstuhl") {
    list.push({
      key: "wheelchair",
      title: "In welchem Block?",
      answered: !!order.seat_rollstuhl_block,
      summary: order.seat_rollstuhl_block || "",
    });
  }

  list.push({
    key: "form",
    title: "Wie möchtest du die Dauerkarte bekommen?",
    answered: !!order.ticket_form,
    summary: labelOf(config.ticketForms, order.ticket_form),
  });

  return list;
});

/**
 * Sobald die offene Frage beantwortet ist, rückt die nächste offene nach.
 * Beantwortet der Nutzer eine frühere Frage neu, bleibt er dort, bis sie
 * wieder beantwortet ist.
 */
watch(
  questions,
  (list) => {
    const current = list.find((q) => q.key === openKey.value);

    if (!current) {
      openKey.value = list.find((q) => !q.answered)?.key || list[list.length - 1].key;
      return;
    }

    if (current.answered) {
      const next = list.find((q) => !q.answered);
      if (next) openKey.value = next.key;
    }
  },
  { immediate: true, deep: true },
);
</script>

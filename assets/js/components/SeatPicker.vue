<template>
  <div>
    <!-- Erst den Block, dann den Platz darin: auf dem Handy sind die Blöcke
         große Flächen, die Sitze im Block bekommen echte Tap-Ziele. -->
    <ArenaPlan v-if="!openBlock" :current="order.seat_block" @select="openBlock = $event" />
    <BlockPlan v-else :block="openBlock" @back="openBlock = null" />

    <details class="mt-6 border-t border-black/10 pt-4">
      <summary class="cursor-pointer text-sm font-medium text-gray-700">
        Platz direkt eingeben
      </summary>

      <div class="grid gap-4 pt-4 sm:grid-cols-3">
      <FormField
        name="seat_block"
        as="select"
        label="Block"
        placeholder="Block wählen"
        :options="blockOptions"
        bare
      />
      <FormField
        name="seat_row"
        as="select"
        label="Reihe"
        placeholder="Reihe wählen"
        :options="rowOptions"
        :disabled="!order.seat_block"
        bare
      />
      <FormField
        name="seat_seat"
        as="select"
        :label="seatCount > 1 ? 'Erster Platz' : 'Platz'"
        placeholder="Platz wählen"
        :options="seatOptions"
        :disabled="!order.seat_row"
        bare
      />
      </div>
    </details>

    <p v-if="seatCount > 1 && order.seat_seat" class="mt-4 text-sm text-gray-700">
      Deine {{ seatCount }} Plätze:
      <span class="font-medium">
        Block {{ order.seat_block }}, Reihe {{ order.seat_row }}, Plätze
        {{ order.seat_seat }}–{{ Number(order.seat_seat) + seatCount - 1 }}
      </span>
    </p>

    <!-- Der Platz ist für dich gesichert, solange die Uhr läuft -->
    <p
      v-if="hold.active"
      class="mt-4 rounded-md bg-steelwhite p-3 text-sm text-gray-800"
      aria-live="polite"
    >
      Plätze noch
      <span class="font-semibold tabular-nums">{{ formatCountdown(hold.secondsLeft) }}</span>
      für dich reserviert.
    </p>

    <div
      v-else-if="hold.expired"
      class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-900"
      role="alert"
    >
      Die Reservierung ist abgelaufen. Der Platz kann inzwischen vergeben sein.
      <button
        type="button"
        class="ml-1 font-medium underline underline-offset-2"
        @click="secureAgain"
      >
        Platz erneut sichern
      </button>
    </div>

    <p
      v-else-if="holdMessage()"
      class="mt-4 rounded-md bg-red-50 p-3 text-sm font-medium text-red-800"
      role="alert"
    >
      {{ holdMessage() }}
    </p>

    <p v-if="selectedIsReserved" class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-900">
      Dieser Platz ist für den bisherigen Dauerkarteninhaber vorbehalten. Du kannst
      ihn wählen – er wird aber erst nach Rücksprache mit dem Ticketing vergeben.
    </p>

    <p class="mt-4 text-xs text-gray-500">
      <span v-if="availability.loading">Belegung wird geprüft …</span>
      <span v-else-if="availability.failed">
        Die Belegung konnte gerade nicht geladen werden – wir prüfen deinen Platz
        spätestens beim Absenden.
      </span>
      <span v-else-if="availability.loadedAt">
        Belegung aktualisiert {{ updatedAgo }}.
      </span>
    </p>
  </div>
</template>

<script setup>
/**
 * Platzauswahl über Block / Reihe / Platz.
 *
 * Die Prüfung der Nachbarplätze für Familienkarten passiert hier wirklich.
 * Im alten Code war sie toter Code: die Liste der gebuchten Plätze wurde nie
 * gefüllt, und `seatLabel + 1` war String-Verkettung ("12" + 1 === "121").
 */
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { ticketPriceFormatted } from "../TicketPriceCalculator.js";
import { formatCountdown, hold, holdMessage, requestHold } from "../seatHold.js";
import {
  availability,
  isReserved,
  isTaken,
  refresh,
  startPolling,
  stopPolling,
} from "../seatAvailability.js";
import { availableBlocks, order, rows, seatCount, seatsInRow } from "../useOrder.js";
import ArenaPlan from "./ArenaPlan.vue";
import BlockPlan from "./BlockPlan.vue";
import FormField from "./FormField.vue";

/**
 * Welcher Block ist im Plan aufgeklappt? Folgt der Auswahl, damit die
 * Eingabe über die Auswahlfelder den Plan mitnimmt -- im alten Plan blieb
 * er stehen und zeigte nicht einmal, welcher Block gewählt war.
 */
const openBlock = ref(order.seat_block || null);

watch(
  () => order.seat_block,
  (block) => {
    if (block) openBlock.value = block;
  },
);

const now = ref(Date.now());
let tick = null;

const blockOptions = computed(() =>
  availableBlocks.value.map((block) => ({
    value: block,
    label: `Block ${block} (${ticketPriceFormatted(
      order.ticket_type,
      order.ticket_category,
      "sitzplatz",
      block,
    )})`,
  })),
);

const rowOptions = computed(() =>
  rows.value.map((row) => ({ value: row, label: `Reihe ${row}` })),
);

const seatOptions = computed(() => {
  const block = order.seat_block;
  const row = order.seat_row;
  if (!block || !row) return [];

  const total = seatsInRow.value.length;
  const need = seatCount.value;

  return seatsInRow.value.map((seat) => {
    const last = seat + need - 1;
    const label = need > 1 ? `Plätze ${seat}–${last}` : `Platz ${seat}`;

    // Passt der ganze Block Plätze überhaupt in die Reihe? Die alte Prüfung
    // rechnete ohne die Gangpositionen und ließ Basisplätze zu, deren letzter
    // Platz gar nicht existierte -- angelegt hat ihn dann der Server.
    if (last > total) {
      return { value: seat, label: `${label} (passt nicht in die Reihe)`, disabled: true };
    }

    const run = Array.from({ length: need }, (_, i) => seat + i);

    if (run.some((s) => isTaken(block, row, s))) {
      return { value: seat, label: `${label} (vergeben)`, disabled: true };
    }

    if (run.some((s) => isReserved(block, row, s))) {
      return { value: seat, label: `${label} (vorbehalten)` };
    }

    return { value: seat, label };
  });
});

const selectedIsReserved = computed(() => {
  if (!order.seat_block || !order.seat_row || !order.seat_seat) return false;

  const base = Number(order.seat_seat);
  return Array.from({ length: seatCount.value }, (_, i) => base + i).some((s) =>
    isReserved(order.seat_block, order.seat_row, s),
  );
});

const updatedAgo = computed(() => {
  const seconds = Math.max(0, Math.round((now.value - availability.loadedAt) / 1000));
  if (seconds < 10) return "gerade eben";
  if (seconds < 60) return `vor ${seconds} Sekunden`;
  return `vor ${Math.round(seconds / 60)} Minuten`;
});

/** Die aktuelle, vollständige Platzauswahl -- oder null. */
const selection = computed(() => {
  if (!order.seat_block || !order.seat_row || !order.seat_seat) return null;

  return {
    block: order.seat_block,
    row: order.seat_row,
    seat: order.seat_seat,
    category: order.ticket_category,
  };
});

function secureAgain() {
  if (selection.value) requestHold(selection.value);
}

/**
 * Sobald die Auswahl vollständig ist, wird der Platz gehalten. Beim
 * Umwählen gibt der Server die vorher gehaltenen Plätze selbst frei.
 */
watch(
  selection,
  (current, previous) => {
    if (!current) return;

    const same =
      previous &&
      previous.block === current.block &&
      previous.row === current.row &&
      previous.seat === current.seat &&
      previous.category === current.category;

    if (!same) requestHold(current);
  },
  { immediate: true },
);

onMounted(() => {
  startPolling();
  tick = setInterval(() => (now.value = Date.now()), 5000);
});

onUnmounted(() => {
  stopPolling();
  if (tick) clearInterval(tick);
});
</script>

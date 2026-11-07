<template>
  <div>
    <div class="flex flex-wrap items-center justify-between gap-3">
      <button
        type="button"
        class="text-sm font-medium text-steelgreen underline underline-offset-2 transition-transform duration-150 ease-out active:scale-[0.97] motion-reduce:transition-none motion-reduce:active:scale-100"
        @click="$emit('back')"
      >
        ← Anderer Block
      </button>

      <button
        type="button"
        class="rounded-md px-3 py-1.5 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 transition-transform duration-150 ease-out active:scale-[0.97] motion-reduce:transition-none motion-reduce:active:scale-100"
        @click="toggleFit"
      >
        {{ fit ? "Vergrößern" : "Ganzen Block zeigen" }}
      </button>
    </div>

    <p class="mt-3 text-sm text-gray-600">
      Block {{ block }} – Eisfläche ist oben.
      <span v-if="!fit" class="text-gray-500">Zum Blättern seitwärts wischen.</span>
    </p>

    <div class="mt-2 overflow-x-auto" :class="fit ? '' : 'pb-2'">
      <svg
        :viewBox="`0 0 ${bounds.width} ${bounds.height}`"
        :width="fit ? '100%' : bounds.width * ZOOM"
        :height="fit ? null : bounds.height * ZOOM"
        :class="fit ? 'block w-full' : 'block'"
        role="group"
        :aria-label="`Plätze in Block ${block}`"
      >
        <g v-for="row in rows" :key="row.rowLabel">
          <text
            :x="0"
            :y="row.y + 3"
            class="fill-gray-400"
            font-size="9"
            text-anchor="middle"
          >
            {{ row.rowLabel }}
          </text>

          <g v-for="seat in row.seats" :key="seat.label">
            <circle
              :cx="seat.x"
              :cy="row.y"
              :r="SEAT_R"
              :fill="COLORS[stateOf(row.rowLabel, seat.label)].fill"
              :stroke="COLORS[stateOf(row.rowLabel, seat.label)].stroke"
              stroke-width="1"
              :tabindex="0"
              role="button"
              :aria-label="ariaFor(row.rowLabel, seat.label)"
              :aria-pressed="stateOf(row.rowLabel, seat.label) === 'selected'"
              class="cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-steelblue"
              @click="pick(row, seat)"
              @keydown.enter.prevent="pick(row, seat)"
              @keydown.space.prevent="pick(row, seat)"
            />
            <text
              :x="seat.x"
              :y="row.y + 3"
              font-size="8"
              text-anchor="middle"
              class="pointer-events-none select-none"
              :fill="COLORS[stateOf(row.rowLabel, seat.label)].text"
            >
              {{ seat.label }}
            </text>
          </g>
        </g>
      </svg>
    </div>

    <!-- Statt window.alert(): der Hinweis steht da, wo er hingehört -->
    <p v-if="note" class="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-900" role="alert">
      {{ note }}
    </p>

    <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-600">
      <li v-for="entry in legend" :key="entry.label" class="flex items-center gap-1.5">
        <span
          class="inline-block h-3 w-3 rounded-full"
          :style="{ background: entry.fill, boxShadow: `inset 0 0 0 1px ${entry.stroke}` }"
        />
        {{ entry.label }}
      </li>
    </ul>
  </div>
</template>

<script setup>
/**
 * Die Plätze eines Blocks.
 *
 * Der alte Canvas-Plan skalierte nur auf Containerbreite: auf 375 px waren
 * die Sitze 6,4 px breit und die Nummern 3,2 px -- faktisch nicht bedienbar.
 * Hier ist der Plan standardmäßig vergrößert und seitwärts scrollbar, mit
 * einem Schalter auf die Gesamtansicht.
 */
import { computed, onMounted, onUnmounted, ref } from "vue";
import { SEAT_R, blockBounds, blockLayout } from "../arenaLayout.js";
import { isReserved, isTaken } from "../seatAvailability.js";
import { order, seatCount, setField } from "../useOrder.js";

const props = defineProps({
  block: { type: String, required: true },
});

defineEmits(["back"]);

/** Faktor, der aus 20 SVG-Einheiten Rasterweite rund 44 px Ziel macht. */
const ZOOM = 2.2;

const COLORS = {
  free: { fill: "#ffffff", stroke: "#4b5563", text: "#111827" },
  selected: { fill: "#046a38", stroke: "#00994c", text: "#ffffff" },
  reserved: { fill: "#fcd34d", stroke: "#b45309", text: "#7c2d12" },
  taken: { fill: "#9ca3af", stroke: "#6b7280", text: "#e5e7eb" },
};

const legend = [
  { label: "frei", ...COLORS.free },
  { label: "deine Auswahl", ...COLORS.selected },
  { label: "für Vorjahresinhaber vorbehalten", ...COLORS.reserved },
  { label: "vergeben", ...COLORS.taken },
];

/**
 * Auf dem Handy startet der Plan vergrößert und seitwärts scrollbar, am
 * Desktop in der Gesamtansicht. Die Gesamtansicht auf 375 px ergäbe knapp
 * 8 px große Sitze -- genau das Problem, das der Umbau beseitigen soll.
 */
const WIDE = "(min-width: 1024px)";
const wide = typeof window !== "undefined" ? window.matchMedia(WIDE) : null;

const fit = ref(wide?.matches ?? true);
/** Sobald der Nutzer selbst umschaltet, bestimmt nicht mehr der Viewport. */
const chosenByUser = ref(false);
const note = ref("");

function onViewportChange(event) {
  if (!chosenByUser.value) fit.value = event.matches;
}

function toggleFit() {
  chosenByUser.value = true;
  fit.value = !fit.value;
}

onMounted(() => wide?.addEventListener("change", onViewportChange));
onUnmounted(() => wide?.removeEventListener("change", onViewportChange));

const rows = computed(() => blockLayout(props.block));
const bounds = computed(() => blockBounds(props.block));

/** Die Plätze, die die aktuelle Auswahl belegt. */
const selected = computed(() => {
  if (order.seat_block !== props.block || !order.seat_row || !order.seat_seat) return [];

  const base = Number(order.seat_seat);
  return Array.from({ length: seatCount.value }, (_, i) => ({
    row: order.seat_row,
    label: base + i,
  }));
});

function stateOf(rowLabel, label) {
  if (selected.value.some((s) => s.row === rowLabel && s.label === label)) return "selected";
  if (isTaken(props.block, rowLabel, label)) return "taken";
  if (isReserved(props.block, rowLabel, label)) return "reserved";
  return "free";
}

/** Passt die ganze Karte ab diesem Platz in die Reihe, und ist sie frei? */
function runProblem(row, label) {
  const need = seatCount.value;
  const total = row.seats.length;

  if (label + need - 1 > total) {
    return `Ab Platz ${label} ist in Reihe ${row.rowLabel} kein Platz mehr für ${need} nebeneinander.`;
  }

  const run = Array.from({ length: need }, (_, i) => label + i);

  if (run.some((s) => isTaken(props.block, row.rowLabel, s))) {
    return need > 1
      ? `Von den ${need} Plätzen ab Platz ${label} ist mindestens einer schon vergeben.`
      : "Dieser Platz ist schon vergeben.";
  }

  return null;
}

function ariaFor(rowLabel, label) {
  const state = stateOf(rowLabel, label);
  const names = {
    free: "frei",
    selected: "ausgewählt",
    reserved: "für den bisherigen Inhaber vorbehalten",
    taken: "vergeben",
  };

  return `Reihe ${rowLabel}, Platz ${label}, ${names[state]}`;
}

function pick(row, seat) {
  const problem = runProblem(row, seat.label);

  if (problem) {
    note.value = problem;
    return;
  }

  note.value = "";

  setField("seat_block", props.block);
  setField("seat_row", row.rowLabel);
  setField("seat_seat", String(seat.label));
}
</script>

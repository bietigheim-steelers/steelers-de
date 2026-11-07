<template>
  <div>
    <svg
      viewBox="110 48 480 581"
      class="block w-full"
      role="group"
      aria-label="Hallenplan: Block wählen"
    >
      <!-- Gebunden statt literal: sonst versucht Vite, den Pfad beim Build
           als Asset aufzulösen. Die Datei liegt in files/, nicht in assets/. -->
      <image :href="PLAN_IMAGE" x="0" y="0" width="700" height="700" />

      <!-- Jeder Block ist ein echtes fokussierbares Element: anders als im
           alten Canvas-Plan geht die Auswahl auch per Tastatur. -->
      <polygon
        v-for="block in blocks"
        :key="block.id"
        :points="block.points"
        :class="[
          'transition-opacity duration-150 ease-steelers motion-reduce:transition-none',
          block.selectable
            ? 'cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-steelblue'
            : 'cursor-not-allowed',
        ]"
        :fill="block.fill"
        :fill-opacity="block.opacity"
        stroke="#046a38"
        :stroke-width="block.id === current ? 3 : 1"
        :tabindex="block.selectable ? 0 : -1"
        :role="block.selectable ? 'button' : 'presentation'"
        :aria-label="block.aria"
        :aria-disabled="block.selectable ? null : 'true'"
        @click="block.selectable && $emit('select', block.id)"
        @keydown.enter.prevent="block.selectable && $emit('select', block.id)"
        @keydown.space.prevent="block.selectable && $emit('select', block.id)"
      />
    </svg>

    <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-600">
      <li class="flex items-center gap-1.5">
        <span class="inline-block h-3 w-3 rounded-sm bg-steelpogreen/50 ring-1 ring-steelgreen" />
        frei
      </li>
      <li class="flex items-center gap-1.5">
        <span class="inline-block h-3 w-3 rounded-sm bg-gray-400/60 ring-1 ring-gray-500" />
        ausverkauft
      </li>
      <li v-if="restricted" class="flex items-center gap-1.5">
        <span class="inline-block h-3 w-3 rounded-sm bg-gray-300/40 ring-1 ring-gray-300" />
        für Familienkarten nicht möglich
      </li>
    </ul>
  </div>
</template>

<script setup>
/**
 * Blockauswahl im Hallenplan.
 *
 * Als SVG statt Canvas: die Blöcke sind damit fokussierbar, haben
 * aria-Labels und lassen sich per Tastatur wählen. Der alte Konva-Plan war
 * reines Canvas, also für Screenreader und Tastatur gar nicht vorhanden.
 */
import { computed } from "vue";
import outlines from "../arenaBlocks.json";
import { blockLayout } from "../arenaLayout.js";
import { availability, isTaken } from "../seatAvailability.js";
import { availableBlocks, order } from "../useOrder.js";
import { isFamilyCategory } from "../config.js";

const PLAN_IMAGE = "/files/steelers/layout/Saalplan_Homepage_1024_1024.png";

const props = defineProps({
  current: { type: String, default: null },
});

defineEmits(["select"]);

const restricted = computed(() => isFamilyCategory(order.ticket_category));

/** Freie Plätze je Block -- hängt an der Belegung und rechnet sich neu. */
const freeSeats = computed(() => {
  // availability.booked lesen, damit das Computed an der Belegung hängt.
  void availability.booked;
  void availability.held;

  const counts = {};

  for (const id of Object.keys(outlines)) {
    let free = 0;

    for (const row of blockLayout(id)) {
      for (const seat of row.seats) {
        if (!isTaken(id, row.rowLabel, seat.label)) free++;
      }
    }

    counts[id] = free;
  }

  return counts;
});

const blocks = computed(() =>
  Object.entries(outlines).map(([id, points]) => {
    const allowed = availableBlocks.value.includes(id);
    const free = freeSeats.value[id] ?? 0;
    const soldOut = free === 0;
    const selectable = allowed && !soldOut;

    let aria = `Block ${id}, ${free} von ${total(id)} Plätzen frei`;
    if (!allowed) aria = `Block ${id} – für diese Kategorie nicht möglich`;
    else if (soldOut) aria = `Block ${id} – ausverkauft`;

    return {
      id,
      points,
      selectable,
      aria,
      fill: !allowed ? "#d1d5db" : soldOut ? "#9ca3af" : "#00994c",
      opacity: !allowed ? 0.4 : soldOut ? 0.6 : id === props.current ? 0.75 : 0.5,
    };
  }),
);

function total(id) {
  return blockLayout(id).reduce((sum, row) => sum + row.seats.length, 0);
}
</script>

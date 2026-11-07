<template>
  <aside
    v-if="price > 0"
    class="sticky bottom-0 z-10 -mx-5 mt-8 border-t border-black/10 bg-white/95 px-5 py-3 backdrop-blur lg:bottom-6 lg:mx-0 lg:rounded-lg lg:border lg:shadow-sm"
    aria-live="polite"
  >
    <div class="flex items-baseline justify-between gap-4">
      <div class="min-w-0">
        <p class="text-xs uppercase tracking-wide text-gray-500">Dein Preis</p>
        <p class="truncate text-sm text-gray-700">{{ description }}</p>
      </div>

      <!-- Der Wert wird kurz weichgezeichnet statt hochgezählt: der Blick
           bemerkt die Änderung, ohne dass die Zahl unlesbar wird. -->
      <p
        class="shrink-0 text-2xl font-semibold text-steelgreen transition-[filter,opacity] duration-150 ease-steelers motion-reduce:transition-none"
        :class="changing ? 'opacity-60 blur-[2px]' : ''"
      >
        {{ formatPrice(price) }}
      </p>
    </div>
  </aside>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { config } from "../config.js";
import { formatPrice } from "../TicketPriceCalculator.js";
import { order, price, seatCount } from "../useOrder.js";

const changing = ref(false);
let timer = null;

watch(price, () => {
  changing.value = true;
  clearTimeout(timer);
  timer = setTimeout(() => (changing.value = false), 150);
});

const description = computed(() => {
  const parts = [
    config.types.find((t) => t.value === order.ticket_type)?.label,
    config.areas.find((a) => a.value === order.ticket_area)?.label,
    config.categoryLabels[order.ticket_category],
  ].filter(Boolean);

  if (seatCount.value > 1) parts.push(`${seatCount.value} Plätze`);

  return parts.join(" · ");
});
</script>

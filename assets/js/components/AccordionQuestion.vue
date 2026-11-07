<template>
  <section
    class="border-b border-black/10 py-5"
    :class="reachable ? '' : 'pointer-events-none opacity-40'"
    :aria-current="open ? 'step' : null"
  >
    <!-- Beantwortet und zugeklappt: eine Zeile mit Ändern-Link -->
    <div v-if="!open" class="flex items-baseline justify-between gap-4">
      <div class="min-w-0">
        <p class="text-sm text-gray-500">{{ title }}</p>
        <p class="truncate text-base font-medium text-gray-900" v-html="summary || '—'" />
      </div>
      <button
        v-if="answered"
        type="button"
        class="shrink-0 text-sm font-medium text-steelgreen underline underline-offset-2 transition-transform duration-150 ease-out active:scale-[0.97] motion-reduce:transition-none motion-reduce:active:scale-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-steelblue"
        @click="$emit('open')"
      >
        Ändern
      </button>
    </div>

    <!-- Offen: die Frage in voller Breite.
         Das 0fr/1fr-Raster ist der Trick für eine Höhenanimation ohne feste
         Pixelwerte und ohne JS-Hooks. -->
    <h3 v-if="open" class="text-lg font-medium leading-tight text-gray-900">
      {{ title }}
    </h3>

    <div class="grid transition-[grid-template-rows] duration-200 ease-steelers motion-reduce:transition-none"
         :class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
      <div class="overflow-hidden">
        <!-- inert nimmt die zugeklappte Frage aus Tabreihenfolge und
             Screenreader-Baum. Ohne das wäre sie nur optisch weg. -->
        <div
          class="transition-opacity duration-200 ease-steelers motion-reduce:transition-none"
          :class="open ? 'opacity-100' : 'opacity-0'"
          :inert="open ? null : true"
        >
          <slot />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  /** Zusammenfassung der Antwort, wenn zugeklappt. */
  summary: { type: String, default: "" },
  open: { type: Boolean, default: false },
  answered: { type: Boolean, default: false },
  /** Vorherige Fragen beantwortet? Sonst ausgegraut. */
  reachable: { type: Boolean, default: true },
});

defineEmits(["open"]);
</script>

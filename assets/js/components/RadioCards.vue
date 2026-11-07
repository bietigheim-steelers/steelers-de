<template>
  <FieldShell
    :id="id"
    :label="label"
    :hint="hint"
    :error="error"
    :required="required"
    :bare="bare"
    as-fieldset
  >
    <template #default="{ describedBy }">
      <fieldset :aria-describedby="describedBy">
        <legend class="sr-only">{{ label }}</legend>

        <div class="space-y-3">
          <label
            v-for="option in options"
            :key="option.value"
            class="relative block rounded-lg border bg-white px-5 py-4 shadow-sm transition-transform duration-150 ease-out motion-reduce:transition-none"
            :class="[
              option.disabled
                ? 'cursor-not-allowed border-gray-200 opacity-60'
                : 'cursor-pointer border-gray-300 active:scale-[0.99] motion-reduce:active:scale-100',
              value === option.value ? 'border-steelgreen ring-2 ring-steelgreen' : '',
            ]"
          >
            <!-- Der Input liegt sr-only unter der Karte: die ganze Fläche ist
                 klickbar, Tastatur und Screenreader bekommen trotzdem ein
                 echtes Radio. -->
            <input
              type="radio"
              class="peer sr-only"
              :name="id"
              :value="option.value"
              :checked="value === option.value"
              :disabled="option.disabled"
              @change="$emit('update', option.value)"
              @blur="$emit('blur')"
            />

            <span
              class="pointer-events-none absolute -inset-px rounded-lg peer-focus-visible:ring-2 peer-focus-visible:ring-steelblue peer-focus-visible:ring-offset-2"
              aria-hidden="true"
            />

            <span class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between">
              <span class="text-base font-medium text-gray-900" v-html="option.label" />
              <span
                v-if="option.note"
                class="mt-1 text-sm text-gray-600 sm:mt-0 sm:ml-4 sm:shrink-0"
                v-html="option.note"
              />
            </span>

            <span
              v-if="option.description"
              class="mt-2 block text-sm leading-relaxed text-gray-600"
              v-html="option.description"
            />
          </label>
        </div>
      </fieldset>
    </template>
  </FieldShell>
</template>

<script setup>
import FieldShell from "./FieldShell.vue";

defineProps({
  id: { type: String, required: true },
  label: { type: String, default: "" },
  value: { type: [String, Number], default: null },
  /** [{ value, label, note?, description?, disabled? }] */
  options: { type: Array, default: () => [] },
  hint: { type: String, default: "" },
  error: { type: String, default: "" },
  required: { type: Boolean, default: true },
  bare: { type: Boolean, default: false },
});

defineEmits(["update", "blur"]);
</script>

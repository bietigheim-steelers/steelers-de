<template>
  <div :class="bare ? '' : 'pb-4 mt-8 border-b border-black/10'">
    <component
      :is="asFieldset ? 'legend' : 'label'"
      v-if="label"
      :for="asFieldset ? null : id"
      class="block text-sm font-medium leading-6 text-gray-900"
    >
      {{ label }}
      <span v-if="!required" class="font-normal text-gray-500">(optional)</span>
    </component>

    <p v-if="hint" :id="`${id}-hint`" class="mt-1 text-sm text-gray-500" v-html="hint" />

    <div class="mt-2">
      <slot :id="id" :describedBy="describedBy" :invalid="!!error" />
    </div>

    <!-- aria-live, damit ein Fehler auch angesagt wird und nicht nur erscheint -->
    <p
      v-if="error"
      :id="`${id}-error`"
      class="mt-2 text-sm font-medium text-red-700"
      role="alert"
    >
      {{ error }}
    </p>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  id: { type: String, required: true },
  label: { type: String, default: "" },
  hint: { type: String, default: "" },
  error: { type: String, default: "" },
  required: { type: Boolean, default: true },
  /** Gruppen von Optionen brauchen fieldset/legend statt label. */
  asFieldset: { type: Boolean, default: false },
  /** Ohne Rahmen und Abstand, z. B. innerhalb eines Rasters. */
  bare: { type: Boolean, default: false },
});

const describedBy = computed(
  () =>
    [props.hint ? `${props.id}-hint` : null, props.error ? `${props.id}-error` : null]
      .filter(Boolean)
      .join(" ") || null,
);
</script>

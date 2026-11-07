<template>
  <FieldShell
    :id="id"
    :label="label"
    :hint="hint"
    :error="error"
    :required="required"
    :bare="bare"
  >
    <template #default="{ describedBy, invalid }">
      <select
        :id="id"
        :value="value ?? ''"
        :disabled="disabled"
        :aria-describedby="describedBy"
        :aria-invalid="invalid || null"
        :aria-required="required || null"
        class="block w-full border-0 bg-white py-1.5 pl-2 pr-8 text-gray-900 sm:text-sm sm:leading-6 ring-1 ring-inset rounded-md shadow-sm focus:ring-2 focus:ring-inset disabled:bg-gray-50 disabled:text-gray-400"
        :class="
          invalid ? 'ring-red-500 focus:ring-red-600' : 'ring-gray-300 focus:ring-steelgreen'
        "
        @change="$emit('update', $event.target.value || null)"
        @blur="$emit('blur')"
      >
        <option value="">{{ placeholder }}</option>
        <option
          v-for="option in options"
          :key="option.value"
          :value="option.value"
          :disabled="option.disabled"
        >
          {{ option.label }}
        </option>
      </select>
    </template>
  </FieldShell>
</template>

<script setup>
import FieldShell from "./FieldShell.vue";

defineProps({
  id: { type: String, required: true },
  label: { type: String, default: "" },
  value: { type: [String, Number], default: "" },
  /** [{ value, label, disabled? }] */
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: "Bitte wählen" },
  hint: { type: String, default: "" },
  error: { type: String, default: "" },
  required: { type: Boolean, default: true },
  disabled: { type: Boolean, default: false },
  bare: { type: Boolean, default: false },
});

defineEmits(["update", "blur"]);
</script>

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
      <textarea
        :id="id"
        :value="value ?? ''"
        :rows="rows"
        :placeholder="placeholder"
        :aria-describedby="describedBy"
        :aria-invalid="invalid || null"
        class="block w-full border-0 bg-white py-1.5 pl-2 text-gray-900 placeholder:text-gray-400 sm:text-sm sm:leading-6 ring-1 ring-inset rounded-md shadow-sm focus:ring-2 focus:ring-inset"
        :class="
          invalid ? 'ring-red-500 focus:ring-red-600' : 'ring-gray-300 focus:ring-steelgreen'
        "
        @input="$emit('update', $event.target.value)"
        @blur="$emit('blur')"
      />
    </template>
  </FieldShell>
</template>

<script setup>
import FieldShell from "./FieldShell.vue";

defineProps({
  id: { type: String, required: true },
  label: { type: String, default: "" },
  value: { type: String, default: "" },
  rows: { type: [String, Number], default: 4 },
  placeholder: { type: String, default: "" },
  hint: { type: String, default: "" },
  error: { type: String, default: "" },
  required: { type: Boolean, default: false },
  bare: { type: Boolean, default: false },
});

defineEmits(["update", "blur"]);
</script>

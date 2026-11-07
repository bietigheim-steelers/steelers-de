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
      <div class="flex rounded-md shadow-sm">
        <input
          :id="id"
          :type="type"
          :value="value ?? ''"
          :placeholder="placeholder"
          :autocomplete="autocomplete"
          :inputmode="inputmode"
          :enterkeyhint="enterkeyhint"
          :aria-describedby="describedBy"
          :aria-invalid="invalid || null"
          :aria-required="required || null"
          class="block flex-1 border-0 bg-white py-1.5 pl-2 text-gray-900 placeholder:text-gray-400 sm:text-sm sm:leading-6 ring-1 ring-inset rounded-md focus:ring-2 focus:ring-inset"
          :class="
            invalid
              ? 'ring-red-500 focus:ring-red-600'
              : 'ring-gray-300 focus:ring-steelgreen'
          "
          @input="$emit('update', $event.target.value)"
          @blur="$emit('blur')"
        />
      </div>
    </template>
  </FieldShell>
</template>

<script setup>
import FieldShell from "./FieldShell.vue";

defineProps({
  id: { type: String, required: true },
  label: { type: String, default: "" },
  value: { type: [String, Number], default: "" },
  type: { type: String, default: "text" },
  placeholder: { type: String, default: "" },
  hint: { type: String, default: "" },
  error: { type: String, default: "" },
  required: { type: Boolean, default: true },
  autocomplete: { type: String, default: null },
  inputmode: { type: String, default: null },
  enterkeyhint: { type: String, default: "next" },
  bare: { type: Boolean, default: false },
});

defineEmits(["update", "blur"]);
</script>

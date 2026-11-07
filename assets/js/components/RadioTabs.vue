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

        <div class="inline-flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300">
          <label
            v-for="(option, index) in options"
            :key="option.value"
            class="relative cursor-pointer px-5 py-2 text-sm font-medium transition-colors duration-150 ease-out motion-reduce:transition-none"
            :class="[
              index === 0 ? 'rounded-l-md' : '',
              index === options.length - 1 ? 'rounded-r-md' : 'border-r border-gray-300',
              value === option.value
                ? 'bg-steelgreen text-white'
                : 'bg-white text-gray-700 hover:bg-gray-50',
              disabled ? 'cursor-not-allowed opacity-60' : '',
            ]"
          >
            <input
              type="radio"
              class="peer sr-only"
              :name="id"
              :value="option.value"
              :checked="value === option.value"
              :disabled="disabled"
              @change="$emit('update', option.value)"
              @blur="$emit('blur')"
            />
            <span
              class="pointer-events-none absolute inset-0 rounded-md peer-focus-visible:ring-2 peer-focus-visible:ring-steelblue peer-focus-visible:ring-offset-2"
              aria-hidden="true"
            />
            {{ option.label }}
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
  /** [{ value, label }] */
  options: { type: Array, default: () => [] },
  hint: { type: String, default: "" },
  error: { type: String, default: "" },
  required: { type: Boolean, default: true },
  disabled: { type: Boolean, default: false },
  bare: { type: Boolean, default: false },
});

defineEmits(["update", "blur"]);
</script>

<template>
  <div :class="bare ? '' : 'mt-6'">
    <div class="flex items-start gap-3">
      <input
        :id="id"
        type="checkbox"
        :checked="!!value"
        :aria-describedby="error ? `${id}-error` : null"
        :aria-invalid="error ? true : null"
        class="mt-1 h-5 w-5 shrink-0 rounded border-gray-300 text-steelgreen focus:ring-2 focus:ring-steelblue focus:ring-offset-2"
        :class="error ? 'border-red-500' : ''"
        @change="$emit('update', $event.target.checked)"
        @blur="$emit('blur')"
      />
      <label :for="id" class="text-sm leading-relaxed text-gray-800">
        <span v-html="label" />
      </label>
    </div>

    <p v-if="error" :id="`${id}-error`" class="mt-2 text-sm font-medium text-red-700" role="alert">
      {{ error }}
    </p>
  </div>
</template>

<script setup>
defineProps({
  id: { type: String, required: true },
  label: { type: String, default: "" },
  value: { type: [Boolean, String, Number], default: false },
  error: { type: String, default: "" },
  bare: { type: Boolean, default: false },
});

defineEmits(["update", "blur"]);
</script>

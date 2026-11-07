<template>
  <nav aria-label="Bestellschritte">
    <ol class="flex items-start gap-2">
      <li v-for="(step, index) in steps" :key="step.name" class="flex-1">
        <component
          :is="index < current ? 'button' : 'div'"
          :type="index < current ? 'button' : null"
          class="block w-full text-left"
          :class="index < current ? 'cursor-pointer' : ''"
          :aria-current="index === current ? 'step' : null"
          @click="index < current ? $emit('go', index) : null"
        >
          <span
            class="block h-1 rounded-full transition-colors duration-200 ease-steelers motion-reduce:transition-none"
            :class="index <= current ? 'bg-steelgreen' : 'bg-gray-200'"
          />
          <span
            class="mt-2 block text-xs sm:text-sm"
            :class="index === current ? 'font-semibold text-gray-900' : 'text-gray-500'"
          >
            {{ step.label }}
          </span>
        </component>
      </li>
    </ol>
  </nav>
</template>

<script setup>
defineProps({
  steps: { type: Array, required: true },
  current: { type: Number, required: true },
});

defineEmits(["go"]);
</script>

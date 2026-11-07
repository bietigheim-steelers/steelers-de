<template>
  <div>
    <FormField
      name="ticket_payment"
      as="cards"
      label="Wie möchtest du bezahlen?"
      :options="config.payment.options"
    />

    <div class="mt-8 rounded-md bg-steelwhite p-5 text-sm leading-relaxed text-gray-800">
      <p class="font-medium">Der Dauerkartenverkauf läuft in zwei Phasen:</p>
      <ul class="mt-2 space-y-1">
        <li v-for="phase in config.salePhases" :key="phase.label">
          <span class="font-medium">{{ phase.label }}:</span>
          {{ phase.from }} bis {{ phase.to }}
          <span class="text-gray-600">
            (Zahlungseingang bis spätestens {{ phase.paymentBy }})
          </span>
        </li>
      </ul>
      <p v-if="config.payment.note" class="mt-3">{{ config.payment.note }}</p>
    </div>
  </div>
</template>

<script setup>
import { config } from "../config.js";
import FormField from "./FormField.vue";
</script>

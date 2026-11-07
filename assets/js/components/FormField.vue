<template>
  <component
    :is="control"
    v-if="visible"
    :id="`dk-${name}`"
    :label="label ?? field?.label ?? ''"
    :value="order[name]"
    :error="shownError"
    :required="isRequired"
    :hint="hint"
    :placeholder="placeholder"
    :options="options"
    :disabled="disabled"
    :bare="bare"
    :rows="rows"
    :type="type"
    :autocomplete="field?.autocomplete ?? null"
    :inputmode="field?.inputmode ?? null"
    @update="(value) => setField(name, value)"
    @blur="() => blurField(name)"
  />
</template>

<script setup>
/**
 * Bindet ein Feld aus formSchema.js an den Bestellzustand.
 *
 * Label, autocomplete und inputmode holt die Komponente aus dem Schema --
 * so steht jede Eigenschaft eines Feldes genau einmal im Projekt.
 */
import { computed } from "vue";
import { fieldByName, isVisible } from "../formSchema.js";
import { blurField, order, setField, ui } from "../useOrder.js";

import TextField from "./TextField.vue";
import TextareaField from "./TextareaField.vue";
import SelectField from "./SelectField.vue";
import RadioCards from "./RadioCards.vue";
import RadioTabs from "./RadioTabs.vue";
import CheckboxField from "./CheckboxField.vue";

const CONTROLS = {
  text: TextField,
  textarea: TextareaField,
  select: SelectField,
  cards: RadioCards,
  tabs: RadioTabs,
  checkbox: CheckboxField,
};

const props = defineProps({
  name: { type: String, required: true },
  as: { type: String, default: "text" },
  label: { type: String, default: null },
  hint: { type: String, default: "" },
  placeholder: { type: String, default: "" },
  options: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false },
  bare: { type: Boolean, default: false },
  rows: { type: [String, Number], default: 4 },
  type: { type: String, default: "text" },
});

const field = computed(() => fieldByName[props.name]);
const control = computed(() => CONTROLS[props.as] || TextField);
const visible = computed(() => (field.value ? isVisible(field.value, order) : true));

const isRequired = computed(() => {
  const required = field.value?.required;
  return typeof required === "function" ? required(order) : !!required;
});

// Fehler erst zeigen, wenn der Nutzer das Feld verlassen hat oder der Schritt
// geprüft wurde -- nicht beim Tippen.
const shownError = computed(() =>
  ui.touched[props.name] ? ui.errors[props.name] || "" : "",
);
</script>

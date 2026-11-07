<template>
  <div
    class="col-span-12 m-auto max-w-full bg-white p-5 shadow-sm lg:max-w-4xl lg:rounded-lg lg:p-10"
  >
    <!-- Geschafft -->
    <div v-if="ui.done">
      <h2 class="text-2xl font-semibold text-gray-900">Danke für deine Bestellung!</h2>
      <p class="mt-3 text-gray-700">
        Du bekommst gleich eine E-Mail mit der Zusammenfassung. Wir prüfen deine
        Bestellung und melden uns nur, wenn etwas unklar ist.
      </p>
      <button type="button" class="btn-default mt-8" @click="restart">
        Weitere Dauerkarte bestellen
      </button>
    </div>

    <template v-else>
      <StepIndicator :current="ui.step" :steps="steps" @go="jumpTo" />

      <!-- Wiederhergestellte Eingaben: sichtbar, nicht still -->
      <div
        v-if="ui.restored"
        class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-md bg-steelwhite p-4 text-sm"
      >
        <p class="text-gray-800">Wir haben deine Eingaben von vorhin behalten.</p>
        <button
          type="button"
          class="font-medium text-steelgreen underline underline-offset-2"
          @click="restart"
        >
          Neu anfangen
        </button>
      </div>

      <h2
        ref="headingRef"
        tabindex="-1"
        class="mt-8 scroll-mt-24 text-2xl font-semibold text-gray-900 focus:outline-none"
      >
        {{ steps[ui.step].label }}
      </h2>

      <form novalidate @submit.prevent="onSubmit">
        <component :is="stepComponent" />

        <!-- Fehler, die nicht an einem Feld hängen -->
        <p
          v-if="ui.formError"
          ref="formErrorRef"
          tabindex="-1"
          role="alert"
          class="mt-6 rounded-md bg-red-50 p-4 text-sm font-medium text-red-800"
        >
          {{ ui.formError }}
        </p>

        <p v-if="stepErrorSummary" role="alert" class="mt-6 text-sm font-medium text-red-700">
          {{ stepErrorSummary }}
        </p>

        <div class="mt-10 flex items-center justify-between gap-4">
          <button
            v-if="ui.step > 0"
            type="button"
            class="text-sm font-medium text-gray-600 underline underline-offset-2 transition-transform duration-150 ease-out active:scale-[0.97] motion-reduce:transition-none motion-reduce:active:scale-100"
            @click="back"
          >
            Zurück
          </button>
          <span v-else />

          <button
            type="submit"
            class="btn-default transition-transform duration-150 ease-out active:scale-[0.97] motion-reduce:transition-none motion-reduce:active:scale-100 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="ui.submitting"
          >
            {{ submitLabel }}
          </button>
        </div>
      </form>

      <PriceBar />
    </template>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { steps } from "../formSchema.js";
import { goToStep, initOrder, nextStep, previousStep, resetAll, submit, ui } from "../useOrder.js";
import PriceBar from "./PriceBar.vue";
import StepIndicator from "./StepIndicator.vue";
import StepDauerkarte from "./StepDauerkarte.vue";
import StepBezahlung from "./StepBezahlung.vue";
import StepKontakt from "./StepKontakt.vue";
import StepUebersicht from "./StepUebersicht.vue";

const STEP_COMPONENTS = [StepDauerkarte, StepBezahlung, StepKontakt, StepUebersicht];

const headingRef = ref(null);
const formErrorRef = ref(null);
const stepErrorSummary = ref("");

const stepComponent = computed(() => STEP_COMPONENTS[ui.step]);
const isLast = computed(() => ui.step === steps.length - 1);

const submitLabel = computed(() => {
  if (ui.submitting) return "Wird gesendet …";
  return isLast.value ? "Verbindlich bestellen" : "Weiter";
});

/** Zum ersten fehlerhaften Feld scrollen UND es fokussieren. */
async function focusFirstInvalid(name) {
  await nextTick();
  const el = document.getElementById(`dk-${name}`);

  if (el) {
    el.scrollIntoView({ block: "center", behavior: prefersReducedMotion() ? "auto" : "smooth" });
    el.focus({ preventScroll: true });
  }
}

function prefersReducedMotion() {
  return window.matchMedia?.("(prefers-reduced-motion: reduce)").matches ?? false;
}

async function focusHeading() {
  await nextTick();
  headingRef.value?.focus({ preventScroll: true });
  headingRef.value?.scrollIntoView({
    block: "start",
    behavior: prefersReducedMotion() ? "auto" : "smooth",
  });
}

async function onSubmit() {
  stepErrorSummary.value = "";

  if (!isLast.value) {
    const failed = nextStep();

    if (failed.length) {
      stepErrorSummary.value =
        failed.length === 1
          ? "Ein Feld fehlt noch."
          : `${failed.length} Felder fehlen noch.`;
      focusFirstInvalid(failed[0]);
    }

    return;
  }

  const result = await submit();

  if (!result.ok && result.firstInvalid) {
    focusFirstInvalid(result.firstInvalid);
  } else if (!result.ok) {
    await nextTick();
    formErrorRef.value?.focus({ preventScroll: true });
    formErrorRef.value?.scrollIntoView({ block: "center" });
  }
}

function back() {
  stepErrorSummary.value = "";
  previousStep();
}

function jumpTo(index) {
  // Zurückspringen ist immer erlaubt, vorspringen nur in geprüfte Schritte.
  if (index < ui.step) goToStep(index);
}

function restart() {
  resetAll();
  stepErrorSummary.value = "";
}

watch(() => ui.step, focusHeading);
watch(() => ui.done, (done) => done && focusHeading());

onMounted(initOrder);
</script>

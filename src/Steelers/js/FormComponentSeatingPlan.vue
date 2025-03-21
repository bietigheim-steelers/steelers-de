<template>
  <div>
    <v-stage ref="stage" :config="config">
      <v-layer>
        <v-rect :config="rectConfig"/>
        <SeatingPlanSection v-for="section in sections" :key="section.id" :section="section"/>
      </v-layer>
    </v-stage>
  </div>
</template>

<script>
import SeatingPlanSection from './SeatingPlanSection.vue';
import seats from './seats.json';

export default {
  name: 'FormComponentSeatingPlan',
  components: {
    SeatingPlanSection,
  },
  setup() {
    return {
      config: {
        width: window.innerWidth,
        height: window.innerHeight,
      },
      rectConfig: {
        x: 20,
        y: 20,
        width: 100,
        height: 100,
        fill: 'red',
        draggable: true,
      },
      sections: seats.sections,
    };
  }
};
</script>

<template>
  <v-stage ref="stageRef" :config="{
    width: stageWidth,
    height: stageHeight,
    scaleX: scale,
    scaleY: scale
  }">
    <v-layer v-if="selectedSection==null" key="sectionSelection">
      <v-image v-if="seatingImage" :config="{
        x: 0,
        y: 0,
        image: seatingImage,
        width: 700,
        height: 700
      }" />
      <v-shape v-for="(config, id) in block" :config="config" @click="clickSection(id)" />
    </v-layer>
    <v-layer v-else key="sectionSelected">
      <SeatingPlanSection :key="'sectionSelected__' + selectedSection.id" :section="selectedSection" />
      <v-rect :config="{
        x: 40,
        y: 640,
        width: 600,
        height: 40,
        fill: '#046a38'
      }" @click="selectedSection = null"></v-rect>
      <v-text :config="{
        x: 300,
        y: 646,
        text: 'Zurück',
        fontSize: 28,
        fill: '#fff'
      }" @click="selectedSection = null" />
    </v-layer>
  </v-stage>
</template>

<script>
import { ref, onMounted, provide, reactive, computed, onUnmounted, nextTick } from 'vue';
import { useImage } from 'vue-konva';
import SeatingPlanSection from './SeatingPlanSection.vue';
import seats from './seats.json';
import { loadSeats } from './SeatingPlanLoad.js'

export default {
  name: 'FormComponentSeatingPlan',
  components: {
    SeatingPlanSection,
  },
  setup() {
    const selectedSection = ref(null);
    const stageRef = ref(null);
    const scale = ref(1);
    const seatRefs = reactive({});
    const sections = seats.sections;
    const [seatingImage] = useImage('/files/steelers/layout/Saalplan_Homepage_1024_1024.png');

    provide('stageRef', stageRef);
    provide('seatRefs', seatRefs);

    const sceneWidth = 700;
    const sceneHeight = 700;

    // Computed properties for stage dimensions
    const stageWidth = computed(() => sceneWidth * scale.value);
    const stageHeight = computed(() => sceneHeight * scale.value);

    onMounted(() => {
      loadSeats()
    });

    const clickSection = (section) => {
      selectedSection.value = sections[section];
    };

    const updateSize = () => {
      const container = document.getElementById("form__season_ticket");
      const containerWidth = container.offsetWidth > 780 ? 700 : container.offsetWidth - 80;
      scale.value = containerWidth / sceneWidth;
    };

    // Add event listeners
    onMounted(() => {
      nextTick(() => {
        updateSize();
      });      
      window.addEventListener('resize', updateSize);
    });

    // Clean up
    onUnmounted(() => {
      window.removeEventListener('resize', updateSize);
    });

    const block = {
      'A': {
        sceneFunc: (context, shape) => {
          context.beginPath();
          context.moveTo(304, 485);
          context.lineTo(395, 485);
          context.lineTo(395, 537);
          context.lineTo(379, 537);
          context.lineTo(379, 562);
          context.lineTo(320, 562);
          context.lineTo(320, 537);
          context.lineTo(304, 537);
          context.closePath();
          context.fillStrokeShape(shape);
        },
        stroke: 'black',
        strokeWidth: 1
      },
      'B': {
        sceneFunc: (context, shape) => {
          context.beginPath();
          context.moveTo(402, 485);
          context.lineTo(493, 485);
          context.lineTo(493, 537);
          context.lineTo(481, 537);
          context.lineTo(481, 562);
          context.lineTo(424, 562);
          context.lineTo(424, 537);
          context.lineTo(402, 537);
          context.closePath();
          context.fillStrokeShape(shape);
        },
        stroke: 'black',
        strokeWidth: 1
      },
      'G': {
        sceneFunc: (context, shape) => {
          context.beginPath();
          context.moveTo(304, 217);
          context.lineTo(304, 166);
          context.lineTo(320, 166);
          context.lineTo(320, 135);
          context.lineTo(282, 135);
          context.lineTo(282, 80);
          context.lineTo(417, 80);
          context.lineTo(417, 135);
          context.lineTo(380, 135);
          context.lineTo(380, 166);
          context.lineTo(395, 166);
          context.lineTo(395, 217);
          context.closePath();
          context.fillStrokeShape(shape);
        },
        stroke: 'black',
        strokeWidth: 1
      },
      'H': {
        sceneFunc: (context, shape) => {
          context.beginPath();
          context.moveTo(206, 217);
          context.lineTo(206, 166);
          context.lineTo(217, 166);
          context.lineTo(217, 90);
          context.lineTo(274, 68);
          context.lineTo(274, 166);
          context.lineTo(297, 166);
          context.lineTo(297, 217);
          context.closePath();
          context.fillStrokeShape(shape);
        },
        stroke: 'black',
        strokeWidth: 1
      },
      'I': {
        sceneFunc: (context, shape) => {
          context.beginPath();
          context.moveTo(131, 157);
          context.lineTo(210, 94);
          context.lineTo(210, 134);
          context.lineTo(188, 134);
          context.lineTo(188, 165);
          context.lineTo(199, 165);
          context.lineTo(199, 217);
          context.lineTo(167, 217);
          context.closePath();
          context.fillStrokeShape(shape);
        },
        stroke: 'black',
        strokeWidth: 1
      },
      'K': {
        sceneFunc: (context, shape) => {
          context.beginPath();
          context.moveTo(167, 485);
          context.lineTo(199, 485);
          context.lineTo(199, 537);
          context.lineTo(188, 537);
          context.lineTo(188, 568);
          context.lineTo(210, 568);
          context.lineTo(210, 609);
          context.lineTo(131, 546);
          context.closePath();
          context.fillStrokeShape(shape);
        },
        stroke: 'black',
        strokeWidth: 1
      },
      'L': {
        sceneFunc: (context, shape) => {
          context.beginPath();
          context.moveTo(206, 485);
          context.lineTo(297, 485);
          context.lineTo(297, 537);
          context.lineTo(275, 537);
          context.lineTo(275, 562);
          context.lineTo(218, 562);
          context.lineTo(218, 537);
          context.lineTo(206, 537);
          context.closePath();
          context.fillStrokeShape(shape);
        },
        stroke: 'black',
        strokeWidth: 1
      },
    };

    return {
      stageWidth,
      stageHeight,
      scale,
      sections,
      stageRef,
      clickSection,
      selectedSection,
      seatingImage,
      block,
    };
  },
};
</script>

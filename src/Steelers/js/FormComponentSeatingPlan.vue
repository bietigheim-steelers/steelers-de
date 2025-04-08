<template>
  <v-stage
    ref="stageRef"
    :config="config"
    @wheel="handleWheel"
    @touchstart="handleTouchStart"
    @touchmove="handleTouchMove"
    @mousedown="handleMouseDown"
    @mousemove="handleMouseMove"
    @mouseup="handleMouseUp"
    @mouseleave="handleMouseUp"
  >
    <v-layer>
      <SeatingPlanSection v-for="section in sections" :key="section.id" :section="section" />
    </v-layer>
  </v-stage>
</template>

<script>
import { ref, onMounted, nextTick, provide, reactive } from 'vue';
import SeatingPlanSection from './SeatingPlanSection.vue';
import seats from './seats.json';
import { loadSeats } from './SeatingPlanLoad.js'

export default {
  name: 'FormComponentSeatingPlan',
  components: {
    SeatingPlanSection,
  },
  setup() {
    const stageRef = ref(null);
    const seatRefs = reactive({}); // Shared object for SeatingPlanSeat refs
    const config = ref({
      width: 700,
      height: 400,
      draggable: false, // Disable default dragging
    });
    const sections = seats.sections;

    const scaleBy = 1.6; // Zoom factor
    const maxScale = 3; // Maximum zoom level
    const minScale = 0.25; // Minimum zoom level
    let lastTouchDistance = 0;
    let isDragging = false;
    let lastPointerPosition = null;

    provide('stageRef', stageRef);
    provide('seatRefs', seatRefs);

    const handleWheel = (e) => {
      e.evt.preventDefault();
      const stage = stageRef.value.getStage();
      const oldScale = stage.scaleX();
      const pointer = stage.getPointerPosition();

      const mousePointTo = {
        x: (pointer.x - stage.x()) / oldScale,
        y: (pointer.y - stage.y()) / oldScale,
      };

      let newScale = e.evt.deltaY > 0 ? oldScale / scaleBy : oldScale * scaleBy;
      newScale = Math.max(minScale, Math.min(maxScale, newScale)); // Clamp scale
      stage.scale({ x: newScale, y: newScale });

      const newPos = {
        x: pointer.x - mousePointTo.x * newScale,
        y: pointer.y - mousePointTo.y * newScale,
      };
      stage.position(newPos);
      stage.batchDraw();
    };

    const handleTouchStart = (e) => {
      if (e.evt.touches.length === 2) {
        e.evt.preventDefault();
        const touch1 = e.evt.touches[0];
        const touch2 = e.evt.touches[1];
        lastTouchDistance = getDistance(touch1, touch2);
      }
    };

    const handleTouchMove = (e) => {
      if (e.evt.touches.length === 2) {
        e.evt.preventDefault();
        const stage = stageRef.value.getStage();
        const touch1 = e.evt.touches[0];
        const touch2 = e.evt.touches[1];
        const newDistance = getDistance(touch1, touch2);

        const oldScale = stage.scaleX();
        let newScale = (oldScale * newDistance) / lastTouchDistance;
        newScale = Math.max(minScale, Math.min(maxScale, newScale)); // Clamp scale
        stage.scale({ x: newScale, y: newScale });

        lastTouchDistance = newDistance;
        stage.batchDraw();
      }
    };

    const handleMouseDown = (e) => {
      isDragging = true;
      const stage = stageRef.value.getStage();
      lastPointerPosition = stage.getPointerPosition();
    };

    const handleMouseMove = (e) => {
      if (!isDragging) return;

      const stage = stageRef.value.getStage();
      const pointerPosition = stage.getPointerPosition();

      if (lastPointerPosition) {
        const dx = pointerPosition.x - lastPointerPosition.x;
        const dy = pointerPosition.y - lastPointerPosition.y;

        stage.position({
          x: stage.x() + dx,
          y: stage.y() + dy,
        });
        stage.batchDraw();
      }

      lastPointerPosition = pointerPosition;
    };

    const handleMouseUp = () => {
      isDragging = false;
      lastPointerPosition = null;
    };

    const getDistance = (p1, p2) => {
      return Math.sqrt(Math.pow(p1.clientX - p2.clientX, 2) + Math.pow(p1.clientY - p2.clientY, 2));
    };

    onMounted(() => {
      loadSeats()
      nextTick(() => {
        const stage = stageRef.value.getStage();
        stage.scale({ x: 0.25, y: 0.25 });
        stage.position({ x: 0, y: 0 });
        stage.batchDraw();
      });
    });

    return {
      config,
      sections,
      stageRef,
      handleWheel,
      handleTouchStart,
      handleTouchMove,
      handleMouseDown,
      handleMouseMove,
      handleMouseUp,
    };
  },
};
</script>

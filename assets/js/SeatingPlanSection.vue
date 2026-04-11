<template>
  <v-group :config="{ x: leftOffset, y: 0 }">
    <v-group v-if="['K', 'L', 'A', 'B', 'C'].includes(section.id)">
      <v-rect :config="{
      x: 0,
      y: 3,
      width: sectionWidth,
      height: 30,
      fill: '#eee'
    }"></v-rect>
      <v-text :config="{
      x: sectionWidth / 2 - 50,
      y: 8,
      text: 'Eisfläche',
      fontSize: 20,
      fill: '#000'
    }" />
    </v-group>
    <v-text :x="x" :y="y" :width="sectionWidth" :height="sectionHeight" verticalAlign="middle" align="center"
      :text="section.id" :fontSize="280" fill="#ccc" />
    <SeatingPlanRow v-for="(row, key) in section.rows" :key="key" :row="row" :rowNumber="key.toString()"
      :section="section" />
    <v-group v-if="['E', 'F', 'G', 'H', 'I'].includes(section.id)">
      <v-rect :config="{
      x: 0,
      y: sectionHeight + 10,
      width: sectionWidth,
      height: 30,
      fill: '#eee'
    }"></v-rect>
      <v-text :config="{
      x: sectionWidth / 2 - 50,
      y: sectionHeight + 15,
      text: 'Eisfläche',
      fontSize: 20,
      fill: '#000'
    }" />
    </v-group>
    </v-group>
</template>

<script>
import SeatingPlanRow from './SeatingPlanRow.vue';

export default {
  name: 'SeatingPlanSection',
  components: {
    SeatingPlanRow,
  },
  props: {
    section: {
      type: Object,
      required: true,
    },
  },
  setup(props) {
    const { section } = props;
    let sectionWidth = 0;
    let sectionHeight = 0;
    let x = 50;
    let y = 50;

    const rowsArray = Object.values(section.rows);
    sectionWidth = 20 * Math.max(...rowsArray.map(row => row.seats + row.grid_start)) + 50;
    sectionHeight = 28 * rowsArray.length;

    const leftOffset = (700 - sectionWidth) / 2;

    if (section.row_direction === 'down' && !section.rows[1].hasOwnProperty('rowLabel')) {
      section.rows = Object.entries(section.rows)
        .reverse()
        .map(([key, row]) => ({ ...row, rowLabel: key }));
    }

    return {
      section,
      sectionWidth,
      sectionHeight,
      leftOffset,
      x,
      y
    };
  }
};
</script>

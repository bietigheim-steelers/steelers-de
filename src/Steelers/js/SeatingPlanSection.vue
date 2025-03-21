<template>
  <v-group>
    <v-text :x="x" :y="y" :width="sectionWidth" :height="sectionHeight" verticalAlign="middle" align="center" :text="section.id"
      fontSize="250" color="#ccc" />
    <SeatingPlanRow v-for="(row, key) in section.rows" :key="key" :row="row" :rowNumber="key" :section="section" />
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
    let x = 0;
    let y = 0;

    const rowsArray = Object.values(section.rows);
    sectionWidth = 22 * Math.max(...rowsArray.map(row => row.seats + row.grid_start));
    sectionHeight = 22 * 16;
    x = 22 * section.grid_start;
    y = 0;
     
    return {
      section,
      sectionWidth,
      sectionHeight,
      x,
      y
    };
  }
};
</script>

<template>
  <v-group>
    <SeatingPlanSeat v-for="seat in row.seats" :key="`${section.id}_${rowNumber}_${seat}`" :rowNumber="rowNumber" :section="section" :seat="seat"
      :row="row" :seatLabel="getSeatLabel(seat)" />
      <v-text :config="{
        x: 0,
        y: textY,
        text: row.rowLabel || rowNumber,
        fontSize: 12,
        align: 'right',
        fill: '#000'
      }" />
      <v-text :config="{
        x: textX,
        y: textY,
        text: row.rowLabel || rowNumber,
        fontSize: 12,
        align: 'right',
        fill: '#000'
      }" />
  </v-group>
</template>

<script>
import SeatingPlanSeat from './SeatingPlanSeat.vue';

export default {
  name: 'SeatingPlanRow',
  components: {
    SeatingPlanSeat,
  },
  props: {
    row: {
      type: Object,
      required: true,
    },
    section: {
      type: Object,
      required: true,
    },
    rowNumber: {
      type: String,
      required: true,
    },
  },
  setup(props) {
    const { row, section, rowNumber } = props;
    const textY = 28 * parseInt(rowNumber) + 14;
    const rowsArray = Object.values(section.rows);
    const textX = 20 * Math.max(...rowsArray.map(row => row.seats + row.grid_start)) + 40;

    row.grid_start = row.grid_start;

    const getSeatLabel = function(seat) {
      let seatLabel = seat;
      if(section.seat_label_direction === 'rtl') {

        seatLabel = (row.seats - seat) + 1
        if (Array.isArray(row.skip)) {
          const skipCount = row.skip.filter(skipSeat => skipSeat >= seat).length;
          seatLabel -= skipCount;
        }
      }
      if(section.seat_label_direction === 'ltl') {

        seatLabel = seat
        if (Array.isArray(row.skip)) {
          const skipCount = row.skip.filter(skipSeat => skipSeat <= seat).length;
          seatLabel -= skipCount;
        }
      }
      return seatLabel.toString();
    }

    return {
      row,
      rowNumber,
      section,
      textY,
      textX,
      getSeatLabel
    };
  }
};
</script>

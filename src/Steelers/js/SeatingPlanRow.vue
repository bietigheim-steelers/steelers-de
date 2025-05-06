<template>
  <v-group>
    <SeatingPlanSeat v-for="seat in row.seats" :key="`${section.id}_${rowNumber}_${seat}`" :rowNumber="rowNumber" :section="section" :seat="seat"
      :row="row" :seatLabel="getSeatLabel(seat)" />
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
      getSeatLabel
    };
  }
};
</script>

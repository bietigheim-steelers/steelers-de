<template>
  <v-group v-if="!row.skip || (row.skip && !row.skip.includes(seat))">
    <v-circle :radius="(seat_size.w / 2)" fill="lightgrey" :strokeWidth=".5" stroke="#444" :x="x" :y="y"></v-circle>
    <v-text :x="x - (seat_size.w/2)" :width="seat_size.w" :height="seat_size.h" :y="y - (seat_size.h / 2)" :text="seatLabel"
      fill="black" :fontSize="(seat_size.w/2)" align="center" verticalAlign="middle" />
  </v-group>
</template>

<script>

export default {
  name: 'SeatingPlanSeat',
  props: {
    seat: {
      type: Number,
      required: true,
    },
    row: {
      type: Object,
      required: true,
    },
    rowNumber: {
      type: String,
      required: true,
    },
    seatLabel: {
      type: String,
      required: true,
    },
  },
  setup(props) {
    const grid_size = {
      "width": 22,
      "height": 22
    }
    const seat_size = {
      "w": 18,
      "h": 18
    }
    const { seat, row, rowNumber, seatLabel } = props;
    const x = grid_size.width * seat + row.grid_start * grid_size.width;
    const y = grid_size.height * parseInt(rowNumber);

    return {
      seat,
      seat_size,
      x,
      y,
      row,
      seatLabel
    };
  }
};
</script>

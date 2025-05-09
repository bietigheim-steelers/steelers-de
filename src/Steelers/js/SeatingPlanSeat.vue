<template>
  <v-group v-if="!row.skip || (row.skip && !row.skip.includes(seat))">
    <v-circle :radius="(seat_size.w / 2)" :fill="colors.circle" :strokeWidth=".5" :stroke="colors.circle_stroke" :x="x"
      :y="y"></v-circle>
    <v-text :x="x - (seat_size.w/2)" @click="clickHandler" @tap="clickHandler" :width="seat_size.w"
      :height="seat_size.h" :y="y - (seat_size.h / 2)" :text="seatLabel" :fill="colors.text" :fontSize="(seat_size.w/2)"
      align="center" verticalAlign="middle" />
  </v-group>
</template>

<script>
import { onMounted, ref, computed, inject } from 'vue';
import { loadSeats } from './SeatingPlanLoad.js'
import reservedSeats from './reservedSeats.json'

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
    section: {
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
    const form$ = inject('form$');

    const seatRef = ref(null); // Local ref for this instance
    const grid_size = {
      "width": 20,
      "height": 28
    }
    const seat_size = {
      "w": 16,
      "h": 16
    }
    const colors = computed(() => {
      if(booked.value) {
        return {
          circle: '#eee',
          circle_stroke: '#ddd',
          text: '#ccc'
        };
      }
      if(selected.value) {
        return {
          circle: '#046a38',
          circle_stroke: 'blue',
          text: 'white'
        };
      }
      if(reserved) {
        return {
          circle: '#dadada',
          circle_stroke: 'blue',
          text: 'blue'
        };
      }
      return {
        circle: '#eee',
        circle_stroke: '#444',
        text: 'black'
      };
    });
    const booked = ref(false)
    const { seat, row, rowNumber, seatLabel, section } = props;
    const x = grid_size.width * seat + row.grid_start * grid_size.width + 20;
    const y = grid_size.height * parseInt(rowNumber) + 20;
    const seatId = `${section.id}_${row.rowLabel || rowNumber}_${seatLabel}`;
    const reserved = reservedSeats.includes(seatId);
    const selected = computed(() => {
      return seatId === `${form$.value.data.seat_block}_${form$.value.data.seat_row}_${form$.value.data.seat_seat}`;
    });


    // Register this instance in the shared refs object
    onMounted(async () => {
      const bookedSeats = await loadSeats();
      booked.value = bookedSeats.includes(seatId);
    });

    const clickHandler = () => {

      if (booked.value) {
        return;
      }

      // Toggle the current seat's selected state
      if (selected.value) {
        form$.value.update({
          seat_block: "",
          seat_seat: "",
          seat_row: "",
        });
      } else {
        form$.value.update({
          seat_block: section.id,
          seat_seat: seatLabel,
          seat_row: row.rowLabel || rowNumber,
        });
      }
    }
    
    return {
      seat,
      seat_size,
      x,
      y,
      colors,
      clickHandler,
      row,
      seatLabel,
      seatRef,
    };
  }
};
</script>

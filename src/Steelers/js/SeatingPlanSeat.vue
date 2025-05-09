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
    const bookedSeats = ref([])
    const { seat, row, rowNumber, seatLabel, section } = props;
    const x = grid_size.width * seat + row.grid_start * grid_size.width + 20;
    const y = grid_size.height * parseInt(rowNumber) + 20;
    const seatId = `${section.id}_${row.rowLabel || rowNumber}_${seatLabel}`;
    const reserved = reservedSeats.includes(seatId);
    const selected = computed(() => {
      const seatRow = row.rowLabel || rowNumber;
      if (form$.value.data.ticket_category && form$.value.data.ticket_category.includes('familie')) {
        if (form$.value.data.ticket_category == 'familie1' || form$.value.data.ticket_category == 'familie2') {
          return form$.value.data.seat_block === section.id && form$.value.data.seat_row === seatRow && (form$.value.data.seat_seat == seatLabel || form$.value.data.seat_seat == parseInt(seatLabel) - 1 || form$.value.data.seat_seat == parseInt(seatLabel) - 2);
        }
        if (form$.value.data.ticket_category == 'familie3') {
          return form$.value.data.seat_block === section.id && form$.value.data.seat_row === seatRow && (form$.value.data.seat_seat == seatLabel || form$.value.data.seat_seat == parseInt(seatLabel) - 1 || form$.value.data.seat_seat == parseInt(seatLabel) - 2 || form$.value.data.seat_seat == parseInt(seatLabel) - 3);
        }
      }
      return seatId === `${form$.value.data.seat_block}_${form$.value.data.seat_row}_${form$.value.data.seat_seat}`;
    });


    // Register this instance in the shared refs object
    onMounted(async () => {
      bookedSeats.value = await loadSeats();
      booked.value = bookedSeats.value.includes(seatId);
    });

    const clickHandler = () => {

      if (booked.value) {
        return;
      }

      if(!row.skip) {
        row.skip = [];
      }

      if (
        form$.value.data.ticket_category &&
        form$.value.data.ticket_category.includes('familie') &&
        (seatLabel == (row.seats - row.skip.length) || seatLabel == row.seats - 1 - row.skip.length)
      ) {
        return;
      }
      if (
        form$.value.data.ticket_category &&
        form$.value.data.ticket_category.includes('familie3') &&
        (seatLabel == row.seats || seatLabel == row.seats - 1 || seatLabel == row.seats - 2)
      ) {
        return;
      }

      // Cancel if any of the four selected seats are already booked
      if (
        form$.value.data.ticket_category &&
        form$.value.data.ticket_category.includes('familie3') &&
        [seatLabel, seatLabel + 1, seatLabel + 2, seatLabel + 3].some(
          (label) => bookedSeats.value.includes(`${section.id}_${row.rowLabel || rowNumber}_${label}`)
        )
      ) {
        return;
      }
      if (
        form$.value.data.ticket_category &&
        (form$.value.data.ticket_category == 'familie1' || form$.value.data.ticket_category == 'familie2') &&
        [seatLabel, seatLabel + 1, seatLabel + 2].some(
          (label) => bookedSeats.value.includes(`${section.id}_${row.rowLabel || rowNumber}_${label}`)
        )
      ) {
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

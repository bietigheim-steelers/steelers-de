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
    const stageRef = inject('stageRef');
    const seatRefs = inject('seatRefs');

    const seatRef = ref(null); // Local ref for this instance
    const grid_size = {
      "width": 22,
      "height": 26
    }
    const seat_size = {
      "w": 18,
      "h": 18
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
      return {
        circle: '#eee',
        circle_stroke: '#444',
        text: 'black'
      };
    });
    const booked = ref(false)
    const selected = ref(false)
    const { seat, row, rowNumber, seatLabel, section } = props;
    const x = grid_size.width * seat + row.grid_start * grid_size.width;
    const y = grid_size.height * parseInt(rowNumber) + section.grid_top * grid_size.height;
    const seatId = `${section.id}_${row.rowLabel || rowNumber}_${seatLabel}`;

    // Register this instance in the shared refs object
    onMounted(async () => {
      const bookedSeats = await loadSeats();
      booked.value = bookedSeats.includes(seatId);
      // Register the entire component instance in the shared refs object
      seatRefs[seatId] = () => {
        selected.value = false; // Reset selected state when the component is unmounted
      };
    });

    const clickHandler = () => {
      if (booked.value) {
        return;
      }

      // Deselect all seats
      Object.values(seatRefs).forEach(ref => {
        ref(); // Access and manipulate the `selected` ref of other instances
      });

      // Toggle the current seat's selected state
      if (selected.value) {
        selected.value = false;
      } else {
        form$.value.update({
          seat_block: section.id,
          seat_seat: seatLabel,
          seat_row: row.rowLabel || rowNumber,
        });
        selected.value = true;
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
      seatRef, // Expose local ref
    };
  }
};
</script>

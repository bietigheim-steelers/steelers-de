<template>
  <GroupElement :name="'my_seat'">
    <div class="col-span-12">
      <TextElement :name="'seat_block'" rules="required" placeholder="Block" />
      <TextElement :name="'seat_row'" @change="onSeatChange" rules="required" placeholder="Reihe" />
      <TextElement :name="'seat_seat'" @change="onSeatChange" rules="required" placeholder="Platz" />
    </div>
    <ul class="col-span-12">
      <li class="grid grid-cols-3 gap-4 w-full" v-for="additonal_seat in additonal_seats">
        <div class="px-4">{{ block }}</div>
        <div class="px-4">Reihe {{ parseInt(row) + 1 }}</div>
        <div class="px-4">Platz {{ additonal_seat }}</div>
      </li>
    </ul>
    <div class="col-span-12" id="seatingPlan" style="border: 1px solid #d1d5db; border-radius: 0.25rem;">
      <FormComponentSeatingPlan></FormComponentSeatingPlan>
    </div>
  </GroupElement>
</template>

<script>
import { inject, computed, ref } from 'vue'
import FormComponentSeatingPlan from "./FormComponentSeatingPlan.vue";

export default {
  components: {
    FormComponentSeatingPlan
  },
  setup() {
    const form$ = inject('form$')

    const additonal_seats = ref([])

    const seat_blocks = computed(() => {
      if (form$.value.data.ticket_category && form$.value.data.ticket_category.includes('familie')) {
        return [
          'Block C',
          'Block I',
          'Block K',
        ]
      } else {
        return [
          'Block A',
          'Block B',
          'Block C',
          'Block F',
          'Block G',
          'Block H',
          'Block I',
          'Block K',
          'Block L',
        ]
      }
    })

    function onSeatChange() {
      let seats = []
      if (form$.value.data.seat_block && form$.value.data.seat_seat && form$.value.data.seat_row) {
        if (form$.value.data.ticket_category.includes('familie')) {
          seats.push(parseInt(form$.value.data.seat_seat) + 2, parseInt(form$.value.data.seat_seat) + 3)
        }
        if (form$.value.data.ticket_category == 'familie3') {
          seats.push(parseInt(form$.value.data.seat_seat) + 4)
        }
      }
      additonal_seats.value = seats
    }

    const max_seats = computed(() => {
      if (form$.value.data.ticket_category == 'familie1' || form$.value.data.ticket_category == 'familie2') {
        return 22
      }
      if (form$.value.data.ticket_category == 'familie3') {
        return 21
      }
      return 24
    })

    const block = computed(() => {
      return form$.value.data.seat_block
    })

    const row = computed(() => {
      return form$.value.data.seat_row
    })

    return {
      seat_blocks,
      max_seats,
      block,
      row,
      onSeatChange,
      additonal_seats,
    }

  },
}
</script>
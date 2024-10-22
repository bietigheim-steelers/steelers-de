<template>
  <GroupElement :name="'my_seat'">
    <SelectElement :name="'seat_block'" @change="onSeatChange" rules="required" placeholder="Block" :native="false"
      :items="seat_blocks" :columns="{
        container: 4,
        label: 12,
        wrapper: 12,
      }" />
    <SelectElement :name="'seat_row'" @change="onSeatChange" rules="required" placeholder="Reihe" :native="false"
      :items="Object.assign({}, Array.from({ length: 21 }, (_, i) => 'Reihe '.concat(i + 1)))" :columns="{
        container: 4,
        label: 12,
        wrapper: 12,
      }" />
    <SelectElement :name="'seat_seat'" @change="onSeatChange" rules="required" placeholder="Platz" :native="false"
      :items="Object.assign({}, Array.from({ length: max_seats }, (_, i) => 'Platz '.concat(i + 1)))" :columns="{
        container: 4,
        label: 12,
        wrapper: 12,
      }" />
    <ul class="col-span-12">
      <li class="grid grid-cols-3 gap-4 w-full" v-for="additonal_seat in additonal_seats">
        <div class="px-4">{{ block }}</div>
        <div class="px-4">Reihe {{ parseInt(row) + 1 }}</div>
        <div class="px-4">Platz {{ additonal_seat }}</div>
      </li>
    </ul>
    <img class="col-start-4 col-span-6"
      src="https://steelers.de/files/steelers/layout/Saalplan_Homepage_1024_1024.webp" />
  </GroupElement>
</template>

<script>
import { inject, computed, ref } from 'vue'

export default {
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
<template>
  <RadiogroupElement rules="required" @change="onCategoryChange" :items="ticket_items" view="blocks">
    <template #label>
      <div class="text-lg leading-tight mt-2">Meine Kategorie:</div>
    </template>
  </RadiogroupElement>
</template>

<script>
import { inject, computed, watch } from 'vue'

export default {
  setup(props, context) {
    const form$ = inject('form$')

    const ticket_items = computed(() => {
      let items = [
        { value: 'vollzahler', label: 'Vollzahler' },
        { value: 'rentner', label: 'Rentner' },
        { value: 'student', label: 'Student' },
        { value: 'azubi', label: 'Auszubildender' },
        { value: 'schueler', label: 'Schüler über 18 Jahre' },
        { value: 'mitglied', label: 'SC Mitglied' },
        { value: 'jugendlich', label: 'Jugendlicher (13-17 Jahre)' },
        { value: 'kind', label: 'Kind (8-12 Jahre)' },
        { value: 'behinderung', label: 'Fan mit Behinderung ab 50%' },
      ]
      if (form$.value.data.ticket_area == 'sitzplatz') {
        items.push(
          { value: 'familie1', label: 'Familienkarte 1 (1 x Vollzahler + 2 Kinder/Jugendliche)' },
          { value: 'familie2', label: 'Familienkarte 2 (2 x Vollzahler + 1 Kind/Jugendlicher)' },
          { value: 'familie3', label: 'Familienkarte 3 (2 x Vollzahler + 2 Kinder/Jugendliche)' }
        )
      }

      if (form$.value.data.ticket_area == 'rollstuhl') {
        items = [
          { value: 'rollstuhl', label: 'Rollstuhlfahrer inkl. Begleitperson' },
        ]
      }
      return items
    })

    const ticket_type = computed(() => {
      return form$.value.data.ticket_type
    })
    const ticket_area = computed(() => {
      return form$.value.data.ticket_area
    })

    const onCategoryChange = (newValue, oldValue) => {
      if (newValue?.includes('familie') || oldValue?.includes('familie')) {
        if (form$.value.el$('ticket_seats.my_seat.seat_block')) {
          form$.value.el$('ticket_seats.my_seat.seat_block').reset()
          form$.value.el$('ticket_seats.my_seat.seat_seat').reset()
          form$.value.el$('ticket_seats.my_seat.seat_row').reset()
        }
      }
    }

// Familienkarte nur in C, I, K
    return {
      ticket_type,
      ticket_area,
      ticket_items,
      onCategoryChange,
    }
  },
}
</script>
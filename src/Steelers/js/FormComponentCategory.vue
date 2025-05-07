<template>
  <RadiogroupElement rules="required" @change="onCategoryChange" :items="ticket_items" view="blocks">
    <template #label>
      <div class="text-lg leading-tight mt-2">Meine Kategorie:</div>
    </template>
  </RadiogroupElement>
</template>

<script>
import { inject, computed } from 'vue'
import { getLowestPrice } from './TicketPriceCalculator.js'

export default {
  setup(props, context) {
    const form$ = inject('form$')

    const getPriceText = (type, category = null, area = null) => {
      return new
        Intl.NumberFormat('de-DE',
          { style: 'currency', currency: 'EUR' }).format(
            getLowestPrice(type, category, area),
          )
    }

    const ticket_items = computed(() => {
      let items = [
        { value: 'vollzahler', label: 'Vollzahler <small>' + getPriceText(form$.value.data.ticket_type, 'vollzahler', form$.value.data.ticket_area) +'</small>' },
        { value: 'rentner', label: 'Rentner <small>' + getPriceText(form$.value.data.ticket_type, 'ermaessigt', form$.value.data.ticket_area) +'</small>' },
        { value: 'student', label: 'Student <small>' + getPriceText(form$.value.data.ticket_type, 'ermaessigt', form$.value.data.ticket_area) +'</small>' },
        { value: 'azubi', label: 'Auszubildender <small>' + getPriceText(form$.value.data.ticket_type, 'ermaessigt', form$.value.data.ticket_area) +'</small>' },
        { value: 'schueler', label: 'Schüler über 18 Jahre <small>' + getPriceText(form$.value.data.ticket_type, 'ermaessigt', form$.value.data.ticket_area) +'</small>' },
        { value: 'mitglied', label: 'SC Mitglied <small>' + getPriceText(form$.value.data.ticket_type, 'ermaessigt', form$.value.data.ticket_area) +'</small>' },
        { value: 'jugendlich', label: 'Jugendlicher (13-17 Jahre) <small>' + getPriceText(form$.value.data.ticket_type, 'jugendlich', form$.value.data.ticket_area) +'</small>' },
        { value: 'kind', label: 'Kind (8-12 Jahre) <small>' + getPriceText(form$.value.data.ticket_type, 'kind', form$.value.data.ticket_area) +'</small>' },
        { value: 'behinderung', label: 'Fan mit Behinderung ab 50% <small>' + getPriceText(form$.value.data.ticket_type, 'behinderung', form$.value.data.ticket_area) +'</small>' },
      ]
      if (form$.value.data.ticket_area == 'sitzplatz') {
        items.push(
          { value: 'familie1', label: 'Familienkarte 1 (1 x Vollzahler + 2 Kinder/Jugendliche) <small>' + getPriceText(form$.value.data.ticket_type, 'familie1', 'C') +'</small>' },
          { value: 'familie2', label: 'Familienkarte 2 (2 x Vollzahler + 1 Kind/Jugendlicher) <small>' + getPriceText(form$.value.data.ticket_type, 'familie2', 'C') +'</small>' },
          { value: 'familie3', label: 'Familienkarte 3 (2 x Vollzahler + 2 Kinder/Jugendliche) <small>' + getPriceText(form$.value.data.ticket_type, 'familie3', 'C') +'</small>' }
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
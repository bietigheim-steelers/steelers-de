<template>
  <div class="bg-black/5 col-span-12 p-8 mt-4 rounded-lg">
    <!-- Dauerkarte details -->
    <div class="col-span-12 flex justify-between">
      <div class="flex items-start mr-6">
        <span class="flex-grow-0 font-bold whitespace-nowrap flex-shrink-0 text-steelgreen">Meine Dauerkarte <b
            class="uppercase">{{
            form_data.ticket_type }}</b>
          2024/2025</span>
      </div>
      <div class="text-sm text-steelblue"><a href="" @click.prevent="handleChangeDauerkarte">Ändern</a></div>
    </div>
    <div class="col-span-12" v-if="form_data.ticket_area == 'stehplatz'">
      Stehplatz - EgeTrans Block - {{ ticket_category }} - <span :class="isFF ? 'line-through' : ''">{{ new
        Intl.NumberFormat('de-DE',
        { style: 'currency', currency: 'EUR' }).format(
        ticket_price,
        ) }}</span><span class="font-bold" v-if="isFF">&nbsp; {{ new
        Intl.NumberFormat('de-DE',
        { style: 'currency', currency: 'EUR' }).format(
        ticket_price / 2,
        ) }}</span><br>
    </div>
    <div class="col-span-12" v-if="form_data.ticket_area == 'sitzplatz'">
      {{ form_data.seat_block }} - Reihe {{ parseInt(form_data.seat_row) + 1 }} - Platz {{ parseInt(form_data.seat_seat)
      + 1 }}
      - {{ ticket_category
      }} - <span :class="isFF ? 'line-through' : ''">{{
        new
        Intl.NumberFormat('de-DE',
        { style: 'currency', currency: 'EUR' }).format(
        ticket_price,
        ) }}</span><span class="font-bold" v-if="isFF">&nbsp; {{ new
        Intl.NumberFormat('de-DE',
        { style: 'currency', currency: 'EUR' }).format(
        ticket_price / 2,
        ) }}</span><br>
    </div>
    <div class="col-span-12" v-if="form_data.ticket_area == 'rollstuhl'">
      {{ form_data.seat_rollstuhl_block }} - Rollstuhlfahrer inkl. Begleitperson
      - <span :class="isFF ? 'line-through' : ''">{{
        new
        Intl.NumberFormat('de-DE',
        { style: 'currency', currency: 'EUR' }).format(
        ticket_price,
        ) }}</span><span class="font-bold" v-if="isFF">&nbsp; {{ new
        Intl.NumberFormat('de-DE',
        { style: 'currency', currency: 'EUR' }).format(
        ticket_price / 2,
        ) }}</span><br>
    </div>
    <!-- Kontakt details -->
    <div class="col-span-12 flex justify-between pt-5 mt-5 border-t border-gray-200 ">
      <div class="flex items-start mr-6">
        <span class="flex-grow-0 font-bold whitespace-nowrap flex-shrink-0 text-steelgreen">Meine
          Kontaktdaten</span>
      </div>
      <div class="text-sm text-steelblue"><a href="" @click.prevent="handleChangeContact">Ändern</a></div>
    </div>
    <div class="col-span-12 pb-2">
      {{ form_data.customer_firstname }} {{ form_data.customer_name }}<br>
      {{ form_data.customer_street }}<br>
      {{ form_data.customer_plz }} {{ form_data.customer_city }}<br>
      {{ form_data.customer_phone }}<br>
      {{ form_data.customer_email }}<br>
    </div>
  </div>
  <TextareaElement name="bemerkung" placeholder="Bemerkung zu meiner Bestellung" />
  <CheckboxElement name="terms" rules="required">
    Ich akzeptiere die Allgemeinen Geschäftsbedinungen der Steelers GmbH sowie die Konditionen und
    Bedingungen zur Dauerkarte 2024/2025. Die AGB sind jederzeit im Internet unter <a href="https://www.steelers.de/agb" target="_blank">https://www.steelers.de/agb</a>
    nachzulesen.  
  </CheckboxElement>
  <CheckboxElement name="data_privacy" rules="required">
    Des Weiteren habe ich die Datenschutzrichtlinie zur Kenntnis genommen. Diese ist auf der
    Geschäftsstelle sowie auf der Homepage der Steelers GmbH unter <a href="https://www.steelers.de/datenschutz" target="_blank">https://www.steelers.de/datenschutz</a> einsehbar. Die Steelers GmbH ist berechtigt, meine
    persönlichen Daten zu nutzen, um eine entsprechende Dauerkarte beim Ticketdienstleister erstellen zu können.
  </CheckboxElement>



</template>

<script>
import { inject, computed } from 'vue'

export default {
  setup(props, context) {
    const form$ = inject('form$')

    const prices = {
      "plus": {
        "A,G": {
          "vollzahler": 720,
          "ermaessigt": 624,
          "jugendlich": 432,
          "kind": 360,
          "behinderung": 360
        },
        "B,F,H,L": {
          "vollzahler": 624,
          "ermaessigt": 528,
          "jugendlich": 384,
          "kind": 312,
          "behinderung": 312
        },
        "C,I,K": {
          "vollzahler": 528,
          "ermaessigt": 456,
          "jugendlich": 312,
          "kind": 264,
          "behinderung": 264,
          "familie1": 792,
          "familie2": 1056,
          "familie3": 1200
        },
        "J": {
          "vollzahler": 384,
          "ermaessigt": 336,
          "jugendlich": 240,
          "kind": 192,
          "behinderung": 192
        },
        "R1,R3,R4": {
          "rollstuhl": 336
        }
      },
      "basic": {
        "A,G": {
          "vollzahler": 570,
          "ermaessigt": 494,
          "jugendlich": 342,
          "kind": 285,
          "behinderung": 285,
        },
        "B,F,H,L": {
          "vollzahler": 494,
          "ermaessigt": 418,
          "jugendlich": 304,
          "kind": 247,
          "behinderung": 247
        },
        "C,I,K": {
          "vollzahler": 418,
          "ermaessigt": 361,
          "jugendlich": 247,
          "kind": 209,
          "behinderung": 209,
          "familie1": 627,
          "familie2": 836,
          "familie3": 950
        },
        "J": {
          "vollzahler": 304,
          "ermaessigt": 266,
          "jugendlich": 190,
          "kind": 152,
          "behinderung": 152
        },
        "R1,R3,R4": {
          "rollstuhl": 266
        }
      }
    }

    const categories = {
      "vollzahler": "Vollzahler",
      "familie1": "Familienkarte 1",
      "familie2": "Familienkarte 2",
      "familie3": "Familienkarte 3",
      "rentner": "Rentner",
      "student": "Student",
      "azubi": "Auszubildender",
      "schueler": "Schüler über 18 Jahre",
      "mitglied": "SC Mitglied",
      "jugendlich": "Jugendlicher (13-17 Jahre)",
      "kind": "Kind (8-12 Jahre)",
      "behinderung": "Fan mit Behinderung ab 50%",
    }

    const ticket_type = computed(() => {
      return form$.value.data.ticket_type
    })
    const ticket_category = computed(() => {
      return categories[form$.value.data.ticket_category]
    })
    const form_data = computed(() => {
      return form$.value.data
    })
    const isFF = computed(() => {
      return !!form$.value.data.ff_new_dk
    })

    const ticket_price = computed(() => {
      let cat = form$.value.data.ticket_category
      if(['rentner', 'student', 'azubi', 'schueler', 'mitglied'].includes(cat)) {
        cat = 'ermaessigt'
      }
      if (form$.value.data.ticket_area === 'stehplatz') {
        return prices[form$.value.data.ticket_type]['J'][cat]
      } else if (form$.value.data.ticket_area === 'rollstuhl') {
        return prices[form$.value.data.ticket_type]['R1,R3,R4']['rollstuhl']
      } else {
        let block = form$.value.data.seat_block.slice(-1)
        switch(block) {
          case 'A':
          case 'G':
            block = 'A,G'
            break
          case 'B':
          case 'F':
          case 'H':
          case 'L':
            block = 'B,F,H,L'
            break
          case 'C':
          case 'I':
          case 'K':
            block = 'C,I,K'
        }
        return prices[form$.value.data.ticket_type][block][cat]
      }
      return 0;
    })

    // `Shipping address` data
    const shipTo = computed(() => {
      const data = form$.value.data
      const parts = ['address', ',', 'address2', ',', 'city', 'state', 'zip_code', ',', 'country']

      return parts.map((part, i) => {
        if (part === ',') {
          return data[parts[i - 1]] ? part : ''
        }

        let value = data[part]

        return value && i > 0 ? ' ' + value : value
      }).join('')
    })



    const handleChangeDauerkarte = () => {
      form$.value.steps$.goTo('my_season_ticket')
    }

    const handleChangeContact = () => {
      form$.value.steps$.goTo('my_information')
    }

    return {
      ticket_type,
      ticket_category,
      form_data,
      shipTo,
      isFF,
      ticket_price,
      handleChangeDauerkarte,
      handleChangeContact,
    }
  },
}
</script>
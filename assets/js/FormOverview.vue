<template>
  <div class="bg-black/5 col-span-12 p-8 mt-4 rounded-lg">
    <!-- Dauerkarte details -->
    <div class="col-span-12 flex justify-between">
      <div class="flex items-start mr-6">
        <span
          class="flex-grow-0 font-bold whitespace-nowrap flex-shrink-0 text-steelgreen"
          >Meine Dauerkarte
          <b class="uppercase">{{ form_data.ticket_type }}</b> 2025/2026</span
        >
      </div>
      <div class="text-sm text-steelblue">
        <a href="" @click.prevent="handleChangeDauerkarte">Ändern</a>
      </div>
    </div>
    <div class="col-span-12" v-if="form_data.ticket_area == 'stehplatz'">
      Stehplatz - EgeTrans Block - {{ ticket_category }} -
      <span :class="isFF ? 'line-through' : ''">{{
        new Intl.NumberFormat("de-DE", {
          style: "currency",
          currency: "EUR",
        }).format(ticket_price)
      }}</span
      ><span class="font-bold" v-if="isFF"
        >&nbsp;
        {{
          new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
          }).format(ticket_price / 2)
        }}</span
      ><br />
    </div>
    <div class="col-span-12" v-if="form_data.ticket_area == 'sitzplatz'">
      {{ form_data.seat_block }} - Reihe {{ parseInt(form_data.seat_row) }} -
      Platz {{ parseInt(form_data.seat_seat) }} - {{ ticket_category }} -
      <span :class="isFF ? 'line-through' : ''">{{
        new Intl.NumberFormat("de-DE", {
          style: "currency",
          currency: "EUR",
        }).format(ticket_price)
      }}</span
      ><span class="font-bold" v-if="isFF"
        >&nbsp;
        {{
          new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
          }).format(ticket_price / 2)
        }}</span
      ><br />
      <template
        v-if="
          form_data.ticket_category == 'familie1' ||
          form_data.ticket_category == 'familie2'
        "
      >
        {{ form_data.seat_block }} - Reihe {{ parseInt(form_data.seat_row) }} -
        Platz {{ parseInt(form_data.seat_seat) + 1 }}
        <br />
        {{ form_data.seat_block }} - Reihe {{ parseInt(form_data.seat_row) }} -
        Platz {{ parseInt(form_data.seat_seat) + 2 }}
        <br />
      </template>
      <template v-if="form_data.ticket_category == 'familie3'">
        {{ form_data.seat_block }} - Reihe {{ parseInt(form_data.seat_row) }} -
        Platz {{ parseInt(form_data.seat_seat) + 3 }}
        <br />
      </template>
    </div>
    <div class="col-span-12" v-if="form_data.ticket_area == 'rollstuhl'">
      {{ form_data.seat_rollstuhl_block }} - Rollstuhlfahrer inkl. Begleitperson
      -
      <span :class="isFF ? 'line-through' : ''">{{
        new Intl.NumberFormat("de-DE", {
          style: "currency",
          currency: "EUR",
        }).format(ticket_price)
      }}</span
      ><span class="font-bold" v-if="isFF"
        >&nbsp;
        {{
          new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
          }).format(ticket_price / 2)
        }}</span
      ><br />
    </div>
    <!-- Kontakt details -->
    <div
      class="col-span-12 flex justify-between pt-5 mt-5 border-t border-gray-200"
    >
      <div class="flex items-start mr-6">
        <span
          class="flex-grow-0 font-bold whitespace-nowrap flex-shrink-0 text-steelgreen"
          >Meine Kontaktdaten</span
        >
      </div>
      <div class="text-sm text-steelblue">
        <a href="" @click.prevent="handleChangeContact">Ändern</a>
      </div>
    </div>
    <div class="col-span-12 pb-2">
      {{ form_data.customer_firstname }} {{ form_data.customer_name }}<br />
      {{ form_data.customer_street }}<br />
      {{ form_data.customer_plz }} {{ form_data.customer_city }}<br />
      {{ form_data.customer_phone }}<br />
      {{ form_data.customer_email }}<br />
    </div>
  </div>
  <TextareaElement
    name="bemerkung"
    placeholder="Bemerkung zu meiner Bestellung"
  />
  <CheckboxElement name="terms" rules="required">
    Ich akzeptiere die Allgemeinen Geschäftsbedinungen der Steelers GmbH sowie
    die Konditionen und Bedingungen zur Dauerkarte 2025/2026. Die AGB sind
    jederzeit im Internet unter
    <a href="https://www.steelers.de/agb" target="_blank"
      >https://www.steelers.de/agb</a
    >
    nachzulesen.
  </CheckboxElement>
  <CheckboxElement name="data_privacy" rules="required">
    Des Weiteren habe ich die Datenschutzrichtlinie zur Kenntnis genommen. Diese
    ist auf der Geschäftsstelle sowie auf der Homepage der Steelers GmbH unter
    <a href="https://www.steelers.de/datenschutz" target="_blank"
      >https://www.steelers.de/datenschutz</a
    >
    einsehbar. Die Steelers GmbH ist berechtigt, meine persönlichen Daten zu
    nutzen, um eine entsprechende Dauerkarte beim Ticketdienstleister erstellen
    zu können.
  </CheckboxElement>
</template>

<script>
import { inject, computed } from "vue";
import { ticketPrice } from "./TicketPriceCalculator.js";

export default {
  setup(props, context) {
    const form$ = inject("form$");

    const categories = {
      vollzahler: "Vollzahler",
      familie1: "Familienkarte 1",
      familie2: "Familienkarte 2",
      familie3: "Familienkarte 3",
      rentner: "Rentner",
      student: "Student",
      azubi: "Auszubildender",
      schueler: "Schüler über 18 Jahre",
      mitglied: "SC Mitglied / PURE Mitglied",
      jugendlich: "Jugendlicher (13-17 Jahre)",
      kind: "Kind (8-12 Jahre)",
      behinderung: "Fan mit Behinderung ab 50%",
    };

    const ticket_type = computed(() => {
      return form$.value.data.ticket_type;
    });
    const ticket_category = computed(() => {
      return categories[form$.value.data.ticket_category];
    });
    const form_data = computed(() => {
      return form$.value.data;
    });
    const isFF = computed(() => {
      return !!form$.value.data.ff_new_dk;
    });

    const ticket_price = computed(() => {
      return ticketPrice(
        form$.value.data.ticket_type,
        form$.value.data.ticket_category,
        form$.value.data.ticket_area,
        form$.value.data.seat_block
      );
    });

    // `Shipping address` data
    const shipTo = computed(() => {
      const data = form$.value.data;
      const parts = [
        "address",
        ",",
        "address2",
        ",",
        "city",
        "state",
        "zip_code",
        ",",
        "country",
      ];

      return parts
        .map((part, i) => {
          if (part === ",") {
            return data[parts[i - 1]] ? part : "";
          }

          let value = data[part];

          return value && i > 0 ? " " + value : value;
        })
        .join("");
    });

    const handleChangeDauerkarte = () => {
      form$.value.steps$.goTo("my_season_ticket");
    };

    const handleChangeContact = () => {
      form$.value.steps$.goTo("my_information");
    };

    return {
      ticket_type,
      ticket_category,
      form_data,
      shipTo,
      isFF,
      ticket_price,
      handleChangeDauerkarte,
      handleChangeContact,
    };
  },
};
</script>

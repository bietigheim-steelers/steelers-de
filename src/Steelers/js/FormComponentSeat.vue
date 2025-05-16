<template>
  <GroupElement :name="'my_seat'">
    <div class="col-span-12">
      <SelectElement
        :name="'seat_block'"
        @change="onSeatChange"
        rules="required"
        placeholder="Block"
        :native="false"
        :items="seat_blocks"
        :columns="{
          container: 4,
          label: 12,
          wrapper: 12,
        }"
      />
      <SelectElement
        :name="'seat_row'"
        @change="onSeatChange"
        rules="required"
        placeholder="Reihe"
        :native="false"
        :items="seat_rows"
        :columns="{
          container: 4,
          label: 12,
          wrapper: 12,
        }"
      />
      <SelectElement
        :name="'seat_seat'"
        @change="onSeatChange"
        rules="required"
        placeholder="Platz"
        :native="false"
        :items="seatsArray"
        :columns="{
          container: 4,
          label: 12,
          wrapper: 12,
        }"
      />
    </div>
    <ul class="col-span-12">
      <li
        class="grid grid-cols-3 gap-4 w-full"
        v-for="additonal_seat in additonal_seats"
      >
        <div class="px-4">{{ block }}</div>
        <div class="px-4">Reihe {{ parseInt(row) }}</div>
        <div class="px-4">Platz {{ additonal_seat }}</div>
      </li>
    </ul>
    <div class="col-span-12 text-sm text-gray-500">
      <p>
        * Rot markierte Sitzplätze sind vorreserviert.<br />Diese können
        zunächst nur von den aktuellen Dauerkarteninhaber bestellt werden.
      </p>
    </div>
    <div
      class="col-span-12"
      id="seatingPlan"
      style="border: 1px solid #d1d5db; border-radius: 0.25rem"
    >
      <FormComponentSeatingPlan></FormComponentSeatingPlan>
    </div>
  </GroupElement>
</template>

<script>
import { inject, computed, ref, onMounted } from "vue";
import FormComponentSeatingPlan from "./FormComponentSeatingPlan.vue";
import seatsImport from "./seats.json";
import { loadSeats } from "./SeatingPlanLoad.js";
import { ticketPriceFormatted } from "./TicketPriceCalculator.js";

export default {
  components: {
    FormComponentSeatingPlan,
  },
  setup() {
    const form$ = inject("form$");
    const additonal_seats = ref([]);
    const seats = JSON.parse(JSON.stringify(seatsImport));
    const bookedSeats = ref([]);

    onMounted(async () => {
      const seatsData = await loadSeats();
      bookedSeats.value = seatsData.booked;
    });
    const seat_blocks = computed(() => {
      if (
        form$.value.data.ticket_category &&
        form$.value.data.ticket_category.includes("familie")
      ) {
        return [
          {
            value: "C",
            label: `C - ${ticketPriceFormatted(
              form$.value.data.ticket_type,
              form$.value.data.ticket_category,
              "sitzplatz",
              "C"
            )}`,
          },
          {
            value: "I",
            label: `I - ${ticketPriceFormatted(
              form$.value.data.ticket_type,
              form$.value.data.ticket_category,
              "sitzplatz",
              "C"
            )}`,
          },
          {
            value: "K",
            label: `K - ${ticketPriceFormatted(
              form$.value.data.ticket_type,
              form$.value.data.ticket_category,
              "sitzplatz",
              "C"
            )}`,
          },
        ];
      } else {
        return [
          {
            value: "A",
            label: `Block A - ${ticketPriceFormatted(
              form$.value.data.ticket_type,
              form$.value.data.ticket_category,
              "sitzplatz",
              "A"
            )}`,
          },
          {
            value: "B",
            label: `Block B - ${ticketPriceFormatted(
              form$.value.data.ticket_type,
              form$.value.data.ticket_category,
              "sitzplatz",
              "B"
            )}`,
          },
          {
            value: "C",
            label: `Block C - ${ticketPriceFormatted(
              form$.value.data.ticket_type,
              form$.value.data.ticket_category,
              "sitzplatz",
              "C"
            )}`,
          },
          {
            value: "F",
            label: `Block F - ${ticketPriceFormatted(
              form$.value.data.ticket_type,
              form$.value.data.ticket_category,
              "sitzplatz",
              "F"
            )}`,
          },
          {
            value: "G",
            label: `Block G - ${ticketPriceFormatted(
              form$.value.data.ticket_type,
              form$.value.data.ticket_category,
              "sitzplatz",
              "G"
            )}`,
          },
          {
            value: "H",
            label: `Block H - ${ticketPriceFormatted(
              form$.value.data.ticket_type,
              form$.value.data.ticket_category,
              "sitzplatz",
              "H"
            )}`,
          },
          {
            value: "I",
            label: `Block I - ${ticketPriceFormatted(
              form$.value.data.ticket_type,
              form$.value.data.ticket_category,
              "sitzplatz",
              "I"
            )}`,
          },
          {
            value: "K",
            label: `Block K - ${ticketPriceFormatted(
              form$.value.data.ticket_type,
              form$.value.data.ticket_category,
              "sitzplatz",
              "K"
            )}`,
          },
          {
            value: "L",
            label: `Block L - ${ticketPriceFormatted(
              form$.value.data.ticket_type,
              form$.value.data.ticket_category,
              "sitzplatz",
              "L"
            )}`,
          },
        ];
      }
    });

    const seat_rows = computed(() => {
      if (form$.value.data.seat_block) {
        return Object.keys(
          seats.sections[form$.value.data.seat_block].rows
        ).map((key) => {
          const row = seats.sections[form$.value.data.seat_block].rows[key];
          return row.hasOwnProperty("rowLabel") ? row.rowLabel : key;
        });
      }
      return [];
    });

    const seatsArray = computed(() => {
      if (form$.value.data.seat_row && form$.value.data.seat_block) {
        let seat_count = 0;
        seat_count =
          seats.sections[form$.value.data.seat_block].rows[
            form$.value.data.seat_row
          ].seats;
        if (
          seats.sections[form$.value.data.seat_block].rows[
            form$.value.data.seat_row
          ].skip
        ) {
          seat_count -=
            seats.sections[form$.value.data.seat_block].rows[
              form$.value.data.seat_row
            ].skip.length;
        }
        return Array.from({ length: seat_count }, (_, i) => {
          const seatNumber = i + 1;
          const disabled = bookedSeats.value.includes(
            `${form$.value.data.seat_block}_${form$.value.data.seat_row}_${seatNumber}`
          );
          return { value: seatNumber, label: `Platz ${seatNumber}`, disabled };
        });
      }
      return [];
    });

    function onSeatChange() {
      let seats = [];
      if (
        form$.value.data.seat_block &&
        form$.value.data.seat_seat &&
        form$.value.data.seat_row
      ) {
        if (form$.value.data.ticket_category.includes("familie")) {
          seats.push(
            parseInt(form$.value.data.seat_seat) + 1,
            parseInt(form$.value.data.seat_seat) + 2
          );
        }
        if (form$.value.data.ticket_category == "familie3") {
          seats.push(parseInt(form$.value.data.seat_seat) + 3);
        }
      }
      additonal_seats.value = seats;
    }

    const max_seats = computed(() => {
      if (
        form$.value.data.ticket_category == "familie1" ||
        form$.value.data.ticket_category == "familie2"
      ) {
        return 22;
      }
      if (form$.value.data.ticket_category == "familie3") {
        return 21;
      }
      return 24;
    });

    const block = computed(() => {
      return form$.value.data.seat_block;
    });

    const row = computed(() => {
      return form$.value.data.seat_row;
    });

    return {
      seat_blocks,
      seat_rows,
      max_seats,
      block,
      seatsArray,
      row,
      onSeatChange,
      additonal_seats,
    };
  },
};
</script>

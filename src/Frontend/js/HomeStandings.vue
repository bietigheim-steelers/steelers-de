<template>
  <div class="mx-2 sm:mx-0 flex place-content-end flex-col h-full">
    <h5 class="uppercase font-bold text-3xl text-center">Tabelle</h5>
    <div>
      <table class="w-full border-separate border-spacing-0.5">
        <thead class="uppercase font-bold">
          <tr>
            <th class="text-center">Platz</th>
            <th class="text-left">Team</th>
            <th class="text-right pr-2">Spiele</th>
            <th class="text-center">Tore</th>
            <th class="text-right pr-2">Pkt.</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="row of standings" :key="`standings-row-${row.id}`">
            <tr>
              <td colspan="5"><hr class="border-t border-gray-400" /></td>
            </tr>
            <tr
              :class="[
                row.tilastotid === '54744' ? 'text-steelgreen font-bold' : '',
              ]"
            >
              <td class="text-center py-2 pr-2">{{ row.rank }}</td>
              <td class="text-left flex items-center py-2">
                <span
                  :title="row.name"
                  class="h-16 w-16 hidden sm:block bg-contain bg-no-repeat bg-center"
                  :style="row.logo ? `background-image: url(${row.logo}` : ''"
                ></span>
                <span class="block sm:ml-4 uppercase">{{ row.name }}</span>
              </td>
              <td class="text-right py-2 pr-2">{{ row.games }}</td>
              <td class="text-center py-2">
                {{ row.goalsfor }}:{{ row.goalsagainst }}
              </td>
              <td class="text-right py-2 pr-2">
                {{ row.points }}
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
    <div class="text-center border-t border-gray-400 mb-12 pt-12">
      <a href="/saison/tabelle" class="btn-default">gesamte Tabelle</a>
    </div>
  </div>
</template>

<script setup>
const standings = window.tilastot_standings;
</script>

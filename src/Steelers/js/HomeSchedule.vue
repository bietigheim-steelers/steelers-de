<template>
  <div class="mx-2 sm:mx-0 flex place-content-between flex-col h-full">
    <h5 class="uppercase font-bold text-3xl text-center">Spiele</h5>
    <div class="grid grid-cols-12 gap-1" satyle="grid-template-rows: 1fr 1fr">
      <div class="col-span-2 self-center">
        <button
          @click="currentIndex--"
          :disabled="currentIndex === 0"
          aria-label="vorheriges Spiel"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            :class="[
              currentIndex === 0
                ? 'fill-slate-800 stroke-slate-800'
                : 'fill-white stroke-white',
              'w-[51px] h-[34px] xl:w-[60px] xl:h-[40px]',
            ]"
            x="0px"
            y="0px"
            viewBox="0 0 60 40"
          >
            <g>
              <line
                stroke-width="2"
                x1="62.5"
                y1="20.031"
                x2="20.5"
                y2="20.031"
              />
              <polygon
                stroke-miterlimit="10"
                points="21,25 15.406,20.093 21,15.06"
              />
              <path
                d="M19.938,2.045c9.866,0,17.893,8.026,17.893,17.893S29.804,37.83,19.938,37.83S2.045,29.804,2.045,19.938
		S10.071,2.045,19.938,2.045 M19.938,0C8.926,0,0,8.926,0,19.938c0,11.011,8.926,19.938,19.938,19.938s19.938-8.927,19.938-19.938
		C39.875,8.926,30.949,0,19.938,0L19.938,0z"
              />
            </g>
          </svg>
        </button>
      </div>
      <div class="col-span-2 place-self-center">
        <div
          :title="currentGame.home.name"
          class="h-16 w-16 sm:h-20 sm:w-20 md:h-32 md:w-32 mx-2 md:mx-6 bg-contain bg-no-repeat bg-center"
          :style="`background-image: url(${currentGame.home.logo})`"
        ></div>
      </div>
      <div
        class="col-span-4 place-self-center text-center font-bold font-headline"
      >
        <span
          v-if="currentGame.ended > 0"
          class="text-4xl md:text-8xl whitespace-nowrap"
          >{{ currentGame.homescore }} : {{ currentGame.awayscore }}</span
        ><span v-else class="text-6xl md:text-8xl whitespace-nowrap">- : -</span
        ><br />
        <span>{{ currentGame.resulttype }}</span>
      </div>
      <div class="col-span-2 place-self-center text-center">
        <div
          :title="currentGame.away.name"
          class="h-16 w-16 sm:h-20 sm:w-20 md:h-32 md:w-32 mx-2 md:mx-6 bg-contain bg-no-repeat bg-center"
          :style="`background-image: url(${currentGame.away.logo})`"
        ></div>
      </div>
      <div class="col-span-2 place-self-center justify-self-end">
        <button
          @click="currentIndex++"
          :disabled="currentIndex + 1 === allGames.length"
          aria-label="nächstes Spiel"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            x="0px"
            y="0px"
            class="rotate-180"
            :class="[
              currentIndex + 1 === allGames.length
                ? 'fill-slate-800 stroke-slate-800'
                : 'fill-white stroke-white',
              'w-[51px] h-[34px] xl:w-[60px] xl:h-[40px]',
            ]"
            viewBox="0 0 60 40"
          >
            <g>
              <line
                fill="none"
                stroke-width="2"
                x1="62.5"
                y1="20.031"
                x2="20.5"
                y2="20.031"
              />
              <polygon
                stroke-miterlimit="10"
                points="21,25 15.406,20.093 21,15.06"
              />
              <path
                d="M19.938,2.045c9.866,0,17.893,8.026,17.893,17.893S29.804,37.83,19.938,37.83S2.045,29.804,2.045,19.938
		S10.071,2.045,19.938,2.045 M19.938,0C8.926,0,0,8.926,0,19.938c0,11.011,8.926,19.938,19.938,19.938s19.938-8.927,19.938-19.938
		C39.875,8.926,30.949,0,19.938,0L19.938,0z"
              />
            </g>
          </svg>
        </button>
      </div>
      <div
        class="col-span-2 col-start-3 place-self-center text-center mt-3 whitespace-nowrap"
      >
        {{ currentGame.home.name }}
      </div>
      <div
        class="col-span-2 col-start-9 place-self-center text-center mt-3 whitespace-nowrap"
      >
        {{ currentGame.away.name }}
      </div>
    </div>
    <div class="border-t border-gray-400 my-5">
      <h6 class="font-bold text-2xl text-center pt-10">
        {{ currentGame.season.name }} - {{ currentGame.season.season }}
        <span v-if="currentGame.gameday > 0"
          >- {{ currentGame.gameday }}. Spieltag</span
        >
      </h6>
      <p class="text-center text-sm mt-3">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5 inline-block mb-0.5 mr-1"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
          />
        </svg>
        <span>{{ currentGame.displayDate }}</span>
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5 inline-block mb-0.5 mr-1 ml-4"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{{ currentGame.displayTime }} Uhr</span>
        <br />
        <template v-if="currentGame.location">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5 inline-block mb-0.5 mr-1 ml-4"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
              clip-rule="evenodd"
            />
          </svg>
          <span>{{ currentGame.location }}</span>
          <br />
        </template>
        <a
          v-if="
            currentGame.gamedate * 1000 > Date.now() &&
            currentGame.home.shortname == 'SCB'
          "
          :href="currentGame.eventimurl ? currentGame.eventimurl : '/tickets'"
          class="link-text--big"
          >Tickets kaufen</a
        >
        <a
          v-else-if="currentGame.magentaurl"
          :href="currentGame.magentaurl"
          class="link-text--big"
          >Highlights anschauen</a
        >
        <span v-else class="link-text--big">&nbsp;</span>
      </p>
    </div>
    <div
      class="text-center border-t border-gray-400 mb-12 pt-12 flex place-content-center gap-5"
    >
      <a href="/saison/spielplan" class="btn-default">gesamter Spielplan</a>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const allGames = window.tilastot_gamedata;

let initValue = 0;
let nextGame = allGames.findIndex((game) => {
  return game.gamedate > Date.now() / 1000;
});
if (nextGame === -1) {
  nextGame = allGames.length;
}
const currentIndex = ref(nextGame > 0 ? nextGame - 1 : nextGame);

const currentGame = computed(() => {
  const game = allGames[currentIndex.value];
  const date = new Date(game.gamedate * 1000);
  game.displayDate = new Intl.DateTimeFormat("de-DE", {
    weekday: "long",
    year: "numeric",
    day: "numeric",
    month: "long",
  }).format(date);
  game.displayTime = game.gametime;
  return game;
});
</script>

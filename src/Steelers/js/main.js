import throttle from "lodash.throttle";
import { createApp } from "vue";
import GLightbox from "glightbox";
import Swiper, { Navigation, Autoplay, Pagination } from "swiper";
import HomeSchedule from "./HomeSchedule.vue";
import HomeStandings from "./HomeStandings.vue";
// import FormApp from './FormApp.vue'
import { tinyDrawer } from "./TinyDrawer";
// import Vueform from '@vueform/vueform'
// import vueformConfig from './../../vueform.config'

createApp(HomeSchedule).mount("#homeschedule");
createApp(HomeStandings).mount("#homestandings");

// Form
// const formApp = createApp(FormApp)
// formApp.use(Vueform, vueformConfig)
// formApp.mount('#formApp')

//Slider

document.querySelectorAll(".swiper-news").forEach((el) => {
  new Swiper(el, {
    slidesPerView: 1,
    spaceBetween: 20,
    modules: [Navigation],
    navigation: {
      nextEl: ".swiper-button-right",
      prevEl: ".swiper-button-left",
    },
    breakpoints: {
      640: {
        slidesPerView: "auto",
      },
    },
  });
});
document.querySelectorAll(".swiper-home").forEach((el) => {
  new Swiper(el, {
    slidesPerView: 1,
    loop: true,
    modules: [Navigation, Autoplay],
    autoplay: {
      delay: 5000,
      pauseOnMouseEnter: true,
    },
    navigation: {
      nextEl: ".swiper-button-right",
      prevEl: ".swiper-button-left",
    },
  });
});

document.querySelectorAll(".swiper-topnews").forEach((el) => {
  new Swiper(el, {
    modules: [Pagination, Autoplay],
    speed: 800,
    loop: true,
    autoplay: {
      delay: 3500,
      disableOnInteraction: false,
    },
    navigation: {
      nextEl: ".swiper-button-right",
      prevEl: ".swiper-button-left",
    },
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
  });
});

document.querySelectorAll(".swiper-shop").forEach((el) => {
  const nextEl = document.querySelector("#homeshop .swiper-button-right");
  const prevEl = document.querySelector("#homeshop .swiper-button-left");
  new Swiper(el, {
    gap: 0,
    slidesPerView: 1,
    modules: [Navigation],
    navigation: {
      nextEl,
      prevEl,
    },
    breakpoints: {
      500: {
        slidesPerView: 2,
      },
      1000: {
        slidesPerView: 3,
      },
    },
    on: {
      slideChangeTransitionEnd: function (swiper) {
        const href = swiper.el
          .querySelector(".swiper-slide-active a")
          .getAttribute("href");

        document.querySelector("#homeshop .buy-now").setAttribute("href", href);
      },
    },
  });
});

document.querySelectorAll(".swiper-gallery").forEach((el) => {
  new Swiper(el, {
    slidesPerView: "auto",
    spaceBetween: 20,
    modules: [Navigation],
    navigation: {
      nextEl: ".swiper-button-right",
      prevEl: ".swiper-button-left",
    },
  });
});

// Main Navigation
tinyDrawer();

//Lightbox
const lightbox = GLightbox({
  touchNavigation: true,
  loop: false,
  autoplayVideos: false,
});
const lightbox2 = GLightbox({
  selector: "data-lightbox",
  touchNavigation: true,
  loop: false,
  autoplayVideos: false,
});

// eventlisteners
window.onscroll = function () {
  stickyNavFunc();
};

var stickyNav = document.getElementsByClassName("top-sticky-nav");
var sticky = 80;

// events
function stickyNavFunc() {
  if (window.pageYOffset > sticky) {
    document.body.classList.add("sticky");
    document.getElementById("container").style.paddingTop = sticky + "px";
  } else {
    document.body.classList.remove("sticky");
    document.getElementById("container").style.paddingTop = 0;
  }
}

function mouseMoveNav(evt) {
  stickyNav[0].style.setProperty("--mouse-x", evt.clientX + "px");
}

const mouseMoveAction = throttle(mouseMoveNav, 20);
stickyNav[0].addEventListener("mousemove", (evt) => {
  mouseMoveAction(evt);
});

// on boot
stickyNavFunc();

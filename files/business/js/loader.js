/*
 * Preloader removed.
 * This file previously ran a decorative loading screen and, only when that
 * screen finished, did it kick off all the reveal/hero animations and release
 * the scroll lock. The loader has been disabled, but we still trigger those
 * animations here so every scroll-reveal keeps working without the loader.
 */
!function () { try { if ("undefined" != typeof window && !window.DEBUG && "undefined" != typeof console) { console.warn = function () { } } } catch (e) { } }();

if ("undefined" != typeof gsap) {
  try { gsap.registerPlugin(ScrollTrigger, SplitText); } catch (e) { }
}

// Start the page at the top (keeps the original loader behaviour).
window.scrollTo(0, 0);
if (window.history && window.history.scrollRestoration) {
  window.history.scrollRestoration = "manual";
}

// Remove any leftover preloader markup and make sure scrolling is never locked.
function removePreloader() {
  var preloader = document.querySelector(".preloader-area");
  if (preloader && preloader.parentNode) {
    preloader.parentNode.removeChild(preloader);
  }
  if (document.body) {
    document.body.classList.remove("overflow-hidden");
    document.body.style.overflow = "";
  }
  if (document.documentElement) {
    document.documentElement.style.overflow = "";
  }
}

// Pre-hide scroll-reveal sections so they don't flash before their
// on-scroll animation runs (this was previously done by the loader).
function hideSectionTitles() {
  if ("undefined" == typeof gsap) return;
  ["[data-section-title]", "[data-journey-section]", "[data-sttr-wrapper]"].forEach(function (selector) {
    document.querySelectorAll(selector).forEach(function (el) {
      gsap.set(el, { opacity: 0, visibility: "hidden" });
    });
  });
}

// Kick off the animations that the loader used to trigger on completion.
function initAllAnimations() {
  removePreloader();
  if ("function" == typeof initHeroAnimation) {
    initHeroAnimation();
  }
  if ("function" == typeof initAnimations) {
    initAnimations();
    setTimeout(function () {
      if ("undefined" != typeof ScrollTrigger) ScrollTrigger.refresh();
    }, 50);
  }
}

document.addEventListener("DOMContentLoaded", function () {
  removePreloader();
  hideSectionTitles();
});

if ("complete" === document.readyState) {
  initAllAnimations();
} else {
  window.addEventListener("load", initAllAnimations);
}

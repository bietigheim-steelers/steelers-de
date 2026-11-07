import { createApp } from "vue";
import OrderApp from "./components/OrderApp.vue";

// Vueform und VueKonva sind raus: der Zustand liegt in useOrder.js, die
// Eingabefelder sind eigene Komponenten, und der Saalplan zeichnet sich
// selbst. Dadurch hängt das Formular an keinem Framework mehr, dessen
// nächste Hauptversion ein Rewrite ist.
createApp(OrderApp).mount("#formApp");

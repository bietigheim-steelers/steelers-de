import { createApp } from "vue";
import FormApp from './FormApp.vue'
import Vueform from '@vueform/vueform'
import VueKonva from 'vue-konva';
import vueformConfig from './../../../vueform.config'

// Form
const formApp = createApp(FormApp)
formApp.use(Vueform, vueformConfig)
formApp.use(VueKonva)
formApp.mount('#formApp')



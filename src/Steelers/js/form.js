import { createApp } from "vue";
import FormApp from './FormApp.vue'
import Vueform from '@vueform/vueform'
import vueformConfig from './../../vueform.config'

// Form
const formApp = createApp(FormApp)
formApp.use(Vueform, vueformConfig)
formApp.mount('#formApp')



import de from '@vueform/vueform/locales/de'
import vueform from '@vueform/vueform/dist/vueform'
import { defineConfig } from '@vueform/vueform'

export default defineConfig({
  theme: vueform,
  locales: { de },
  endpoints: {
    submit: {
      url: '/seasonticket/order',
      method: 'post'
    }
  },
  locale: 'de',
  size: 'lg',
  validateOn: 'change|step',
})
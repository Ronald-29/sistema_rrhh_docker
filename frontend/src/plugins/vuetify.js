import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'

import { createVuetify } from 'vuetify'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import { es } from 'vuetify/locale'

const campos = {
  variant: 'outlined',
  density: 'comfortable'
}

export default createVuetify({
  locale: {
    locale: 'es',
    messages: { es }
  },
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: { mdi }
  },
  theme: {
    // Vuetify 4 usa 'system' por defecto (tema oscuro si el sistema operativo lo tiene).
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#1F4E79',
          secondary: '#2E7D32',
          background: '#F4F6F9'
        }
      }
    }
  },
  defaults: {
    VTextField: campos,
    VTextarea: { ...campos, rows: 3, autoGrow: true },
    VSelect: campos,
    VAutocomplete: campos,
    // En pantallas chicas cada fila se muestra apilada, como tarjeta.
    VDataTable: { density: 'comfortable', hover: true, mobileBreakpoint: 'sm' },
    VCard: { rounded: 'lg' }
  }
})

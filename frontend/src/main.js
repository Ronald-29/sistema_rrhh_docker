import { createApp } from 'vue'

import App from './App.vue'
import vuetify from './plugins/vuetify'
import router from './router'
import { alVencerSesion } from './services/api'
import { olvidarSesion, sesion } from './services/sesion'
import { notificar } from './services/notificaciones'

alVencerSesion((respuesta) => {
  // Un 401 antes de iniciar sesión (por ejemplo al consultar /me) no es una sesión vencida.
  if (!sesion.autenticado) {
    return
  }

  olvidarSesion()

  const rutaActual = router.currentRoute.value

  if (rutaActual.name !== 'login') {
    // El backend avisa con USUARIO_DESACTIVADO cuando el usuario fue dado de baja.
    notificar.error(
      respuesta?.codigo === 'USUARIO_DESACTIVADO'
        ? respuesta.message
        : 'Su sesión venció. Inicie sesión nuevamente.'
    )
    router.push({ name: 'login', query: { redirigir: rutaActual.fullPath } })
  }
})

createApp(App)
  .use(vuetify)
  .use(router)
  .mount('#app')

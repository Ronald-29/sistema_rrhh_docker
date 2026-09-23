import { reactive } from 'vue'

// Estado del aviso flotante (v-snackbar) que muestra App.vue.
export const notificacion = reactive({
  visible: false,
  mensaje: '',
  color: 'success'
})

function mostrar(mensaje, color) {
  notificacion.mensaje = mensaje
  notificacion.color = color
  notificacion.visible = true
}

export const notificar = {
  exito: (mensaje) => mostrar(mensaje, 'success'),
  error: (errorOMensaje) =>
    mostrar(
      typeof errorOMensaje === 'string'
        ? errorOMensaje
        : errorOMensaje?.message ?? 'Ocurrió un error inesperado.',
      'error'
    )
}

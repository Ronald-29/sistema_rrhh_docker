import { ref } from 'vue'

// Estado de carga y error para una consulta al backend.
export function useCarga() {
  const cargando = ref(false)
  const error = ref('')

  async function ejecutar(tarea) {
    cargando.value = true
    error.value = ''

    try {
      return await tarea()
    } catch (excepcion) {
      error.value = excepcion.message ?? 'Ocurrió un error inesperado.'
      return undefined
    } finally {
      cargando.value = false
    }
  }

  return { cargando, error, ejecutar }
}

// Envío de un formulario: guarda el mensaje de error para mostrarlo dentro del diálogo.
export function useGuardado() {
  const guardando = ref(false)
  const error = ref('')

  async function guardar(tarea) {
    guardando.value = true
    error.value = ''

    try {
      await tarea()
      return true
    } catch (excepcion) {
      error.value = excepcion.message ?? 'Ocurrió un error inesperado.'
      return false
    } finally {
      guardando.value = false
    }
  }

  return { guardando, error, guardar }
}

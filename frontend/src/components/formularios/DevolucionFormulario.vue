<script setup>
import { computed, ref, watch } from 'vue'

import DialogoFormulario from '@/components/DialogoFormulario.vue'
import { useGuardado } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'
import { sesion } from '@/services/sesion'
import { fechaHora, reglas } from '@/utils/formato'

const abierto = defineModel({ type: Boolean, default: false })

const props = defineProps({
  prestamo: { type: Object, required: true }
})

const emit = defineEmits(['guardado'])

const { guardando, error, guardar } = useGuardado()

const formulario = ref({})

const descripcion = computed(() =>
  props.prestamo.id_documento
    ? `Documento: ${props.prestamo.nombre_documento ?? `#${props.prestamo.id_documento}`}`
    : `Expediente N° ${props.prestamo.numero_correlativo ?? props.prestamo.id_expediente}`
)

// immediate: las vistas crean este componente con v-if y el diálogo ya abierto.
// Se vigila también el préstamo por si se abre otro sin cerrar el componente.
watch([abierto, () => props.prestamo.id_prestamo], ([valor]) => {
  if (!valor) {
    return
  }

  error.value = ''

  // Lo más común: devuelve quien lo recibió y lo recibe el usuario del archivo.
  formulario.value = {
    devuelto_por: props.prestamo.recibido_por ?? '',
    recibido_devolucion_por: sesion.nombreVisible,
    observaciones_devolucion: ''
  }
}, { immediate: true })

async function enviar() {
  let respuesta

  const exito = await guardar(async () => {
    respuesta = await api.put(`/prestamos/${props.prestamo.id_prestamo}/devolucion`, {
      devuelto_por: formulario.value.devuelto_por.trim(),
      recibido_devolucion_por: formulario.value.recibido_devolucion_por.trim(),
      observaciones_devolucion: formulario.value.observaciones_devolucion
    })
  })

  if (exito) {
    notificar.exito(respuesta.message)
    abierto.value = false
    emit('guardado', respuesta)
  }
}
</script>

<template>
  <DialogoFormulario
    v-model="abierto"
    titulo="Registrar devolución"
    texto-guardar="Registrar devolución"
    color-guardar="success"
    :guardando="guardando"
    :error="error"
    @guardar="enviar"
  >
    <v-alert type="info" variant="tonal" density="compact" class="mb-4">
      <div>{{ descripcion }}</div>
      <div class="text-body-small">
        Entregado por {{ prestamo.entregado_por }} a {{ prestamo.recibido_por }}
        el {{ fechaHora(prestamo.fecha_hora_entrega) }}
      </div>
    </v-alert>

    <v-row density="comfortable">
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.devuelto_por"
          label="Devuelto por *"
          :rules="[reglas.obligatorio, reglas.maximo(150)]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.recibido_devolucion_por"
          label="Recibido por *"
          hint="Quien recibe la devolución en el archivo."
          :rules="[reglas.obligatorio, reglas.maximo(150)]"
        />
      </v-col>
      <v-col cols="12">
        <v-textarea v-model="formulario.observaciones_devolucion" label="Observaciones de la devolución" />
      </v-col>
    </v-row>

    <p class="text-body-small text-medium-emphasis">La fecha y hora de devolución se registran automáticamente.</p>
  </DialogoFormulario>
</template>

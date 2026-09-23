<script setup>
import { ref, watch } from 'vue'

import DialogoFormulario from '@/components/DialogoFormulario.vue'
import { useGuardado } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'
import { sesion } from '@/services/sesion'
import { ESTADOS_EXPEDIENTE, reglas } from '@/utils/formato'

const abierto = defineModel({ type: Boolean, default: false })

const props = defineProps({
  expediente: { type: Object, required: true }
})

const emit = defineEmits(['guardado'])

const { guardando, error, guardar } = useGuardado()

const formulario = ref({})

watch(abierto, (valor) => {
  if (!valor) {
    return
  }

  error.value = ''

  // Al revisar se confirma o corrige el estado y las observaciones del expediente.
  formulario.value = {
    revisado_por: sesion.nombreVisible,
    estado_expediente: props.expediente.estado_expediente,
    observaciones: props.expediente.observaciones ?? ''
  }
})

async function enviar() {
  let respuesta

  const exito = await guardar(async () => {
    respuesta = await api.put(`/expedientes/${props.expediente.id_expediente}/revision`, {
      revisado_por: formulario.value.revisado_por.trim(),
      estado_expediente: formulario.value.estado_expediente,
      observaciones: formulario.value.observaciones
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
    titulo="Registrar revisión física"
    texto-guardar="Registrar revisión"
    color-guardar="secondary"
    :guardando="guardando"
    :error="error"
    @guardar="enviar"
  >
    <p class="text-body-medium text-medium-emphasis mb-4">
      Confirme el estado del expediente N° {{ expediente.numero_correlativo }} después de
      revisarlo físicamente.
    </p>

    <v-row density="comfortable">
      <v-col cols="12">
        <v-text-field
          v-model="formulario.revisado_por"
          label="Revisado por *"
          hint="Persona que revisó físicamente el expediente."
          :rules="[reglas.obligatorio, reglas.maximo(150)]"
        />
      </v-col>
      <v-col cols="12">
        <v-select
          v-model="formulario.estado_expediente"
          :items="ESTADOS_EXPEDIENTE"
          label="Estado del expediente *"
          :rules="[reglas.obligatorio]"
        />
      </v-col>
      <v-col cols="12">
        <v-textarea
          v-model="formulario.observaciones"
          label="Observaciones"
          hint="Documentos faltantes, hojas deterioradas, etc."
        />
      </v-col>
    </v-row>
  </DialogoFormulario>
</template>

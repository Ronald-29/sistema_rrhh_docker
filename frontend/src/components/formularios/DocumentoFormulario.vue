<script setup>
import { computed, ref, watch } from 'vue'

import DialogoFormulario from '@/components/DialogoFormulario.vue'
import { useGuardado } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'
import { ESTADOS_DOCUMENTO_EDITABLES, limpiarFormulario, reglas } from '@/utils/formato'

const abierto = defineModel({ type: Boolean, default: false })

const props = defineProps({
  idExpediente: { type: Number, required: true },
  documento: { type: Object, default: null }
})

const emit = defineEmits(['guardado'])

const { guardando, error, guardar } = useGuardado()

const formulario = ref(vacio())

const editando = computed(() => props.documento !== null)

function vacio() {
  return {
    nombre_documento: '',
    tipo_documento: '',
    numero_documento: '',
    fecha_documento: '',
    estado_documento: 'DISPONIBLE',
    observaciones: ''
  }
}

watch(abierto, (valor) => {
  if (!valor) {
    return
  }

  error.value = ''

  formulario.value = editando.value
    ? Object.fromEntries(
        Object.keys(vacio()).map((clave) => [clave, props.documento[clave] ?? ''])
      )
    : vacio()
})

async function enviar() {
  const datos = {
    ...limpiarFormulario(formulario.value),
    id_expediente: props.idExpediente
  }
  let respuesta

  const exito = await guardar(async () => {
    respuesta = editando.value
      ? await api.put(`/documentos/${props.documento.id_documento}`, datos)
      : await api.post('/documentos', datos)
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
    :titulo="editando ? 'Editar documento' : 'Agregar documento'"
    :guardando="guardando"
    :error="error"
    @guardar="enviar"
  >
    <v-row density="comfortable">
      <v-col cols="12">
        <v-text-field
          v-model="formulario.nombre_documento"
          label="Nombre del documento *"
          hint="Por ejemplo: Memorándum de designación"
          :rules="[reglas.obligatorio, reglas.maximo(150)]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.tipo_documento"
          label="Tipo de documento"
          hint="Memorándum, contrato, certificado..."
          :rules="[reglas.maximo(100)]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.numero_documento"
          label="N° de documento"
          :rules="[reglas.maximo(50)]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field v-model="formulario.fecha_documento" label="Fecha del documento" type="date" />
      </v-col>
      <v-col cols="12" sm="6">
        <v-select
          v-model="formulario.estado_documento"
          :items="ESTADOS_DOCUMENTO_EDITABLES"
          label="Estado *"
          :rules="[reglas.obligatorio]"
        />
      </v-col>
      <v-col cols="12">
        <v-textarea v-model="formulario.observaciones" label="Observaciones" />
      </v-col>
    </v-row>
  </DialogoFormulario>
</template>

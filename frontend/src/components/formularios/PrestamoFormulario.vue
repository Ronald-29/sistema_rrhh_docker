<script setup>
import { computed, ref, watch } from 'vue'

import DialogoFormulario from '@/components/DialogoFormulario.vue'
import { useCarga, useGuardado } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'
import { sesion } from '@/services/sesion'
import { nombreCompleto, reglas } from '@/utils/formato'

const abierto = defineModel({ type: Boolean, default: false })

const props = defineProps({
  // Si se indica, el préstamo es de ese expediente o documento y no se elige en el formulario.
  expediente: { type: Object, default: null },
  documento: { type: Object, default: null }
})

const emit = defineEmits(['guardado'])

const { guardando, error, guardar } = useGuardado()
const { cargando: cargandoOpciones, error: errorOpciones, ejecutar } = useCarga()

const tipo = ref('EXPEDIENTE')
const idExpediente = ref(null)
const idDocumento = ref(null)
const formulario = ref({})
const expedientes = ref([])
const documentos = ref([])

const predefinido = computed(() => props.expediente !== null || props.documento !== null)

const descripcionPredefinida = computed(() =>
  props.documento
    ? `Documento: ${props.documento.nombre_documento}`
    : `Expediente N° ${props.expediente?.numero_correlativo} - ${nombreCompleto(props.expediente)}`
)

const opcionesExpediente = computed(() =>
  expedientes.value.map((expediente) => ({
    value: expediente.id_expediente,
    title: `${expediente.numero_correlativo} - ${nombreCompleto(expediente)}`
  }))
)

const opcionesDocumento = computed(() =>
  documentos.value
    .filter((documento) => documento.estado_documento === 'DISPONIBLE')
    .map((documento) => ({
      value: documento.id_documento,
      title: documento.nombre_documento,
      subtitle: [documento.tipo_documento, documento.numero_documento].filter(Boolean).join(' · ')
    }))
)

watch(abierto, async (valor) => {
  if (!valor) {
    return
  }

  error.value = ''
  tipo.value = props.documento ? 'DOCUMENTO' : 'EXPEDIENTE'
  idExpediente.value = props.expediente?.id_expediente ?? null
  idDocumento.value = props.documento?.id_documento ?? null
  documentos.value = []

  formulario.value = {
    entregado_por: sesion.nombreVisible,
    recibido_por: '',
    motivo: '',
    observaciones_entrega: ''
  }

  if (!predefinido.value) {
    await ejecutar(async () => {
      expedientes.value = (await api.get('/expedientes')).expedientes
    })
  }
})

// Al prestar un documento, primero se elige su expediente y luego el documento.
watch([tipo, idExpediente], async ([tipoActual, expedienteActual]) => {
  if (predefinido.value) {
    return
  }

  idDocumento.value = null
  documentos.value = []

  if (tipoActual === 'DOCUMENTO' && expedienteActual) {
    await ejecutar(async () => {
      documentos.value = (await api.get(`/expedientes/${expedienteActual}/documentos`)).documentos
    })
  }
})

async function enviar() {
  const datos = {
    id_expediente: tipo.value === 'EXPEDIENTE' ? idExpediente.value : null,
    id_documento: tipo.value === 'DOCUMENTO' ? idDocumento.value : null,
    entregado_por: formulario.value.entregado_por.trim(),
    recibido_por: formulario.value.recibido_por.trim(),
    motivo: formulario.value.motivo,
    observaciones_entrega: formulario.value.observaciones_entrega
  }
  let respuesta

  const exito = await guardar(async () => {
    respuesta = await api.post('/prestamos', datos)
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
    titulo="Registrar préstamo"
    texto-guardar="Registrar préstamo"
    :guardando="guardando"
    :error="error || errorOpciones"
    ancho="680"
    @guardar="enviar"
  >
    <v-alert v-if="predefinido" type="info" variant="tonal" density="compact" class="mb-4">
      {{ descripcionPredefinida }}
    </v-alert>

    <v-row density="comfortable">
      <template v-if="!predefinido">
        <v-col cols="12">
          <v-btn-toggle v-model="tipo" mandatory color="primary" variant="outlined" divided class="mb-2">
            <v-btn value="EXPEDIENTE" prepend-icon="mdi-folder-outline">Expediente completo</v-btn>
            <v-btn value="DOCUMENTO" prepend-icon="mdi-file-document-outline">Un documento</v-btn>
          </v-btn-toggle>
        </v-col>
        <v-col cols="12">
          <v-autocomplete
            v-model="idExpediente"
            :items="opcionesExpediente"
            :loading="cargandoOpciones"
            label="Expediente *"
            no-data-text="No se encontraron expedientes."
            :rules="[reglas.obligatorio]"
          />
        </v-col>
        <v-col v-if="tipo === 'DOCUMENTO'" cols="12">
          <v-autocomplete
            v-model="idDocumento"
            :items="opcionesDocumento"
            :loading="cargandoOpciones"
            :disabled="!idExpediente"
            label="Documento *"
            :no-data-text="idExpediente ? 'El expediente no tiene documentos disponibles.' : 'Primero elija un expediente.'"
            :rules="[reglas.obligatorio]"
          >
            <template #item="{ props: propiedades, item }">
              <v-list-item v-bind="propiedades" :subtitle="item.subtitle" />
            </template>
          </v-autocomplete>
        </v-col>
      </template>

      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.entregado_por"
          label="Entregado por *"
          hint="Quien entrega el expediente o documento."
          :rules="[reglas.obligatorio, reglas.maximo(150)]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.recibido_por"
          label="Recibido por *"
          hint="Quien lo recibe en préstamo."
          :rules="[reglas.obligatorio, reglas.maximo(150)]"
        />
      </v-col>
      <v-col cols="12">
        <v-text-field v-model="formulario.motivo" label="Motivo" :rules="[reglas.maximo(250)]" />
      </v-col>
      <v-col cols="12">
        <v-textarea v-model="formulario.observaciones_entrega" label="Observaciones de la entrega" />
      </v-col>
    </v-row>

    <p class="text-body-small text-medium-emphasis">La fecha y hora de entrega se registran automáticamente.</p>
  </DialogoFormulario>
</template>

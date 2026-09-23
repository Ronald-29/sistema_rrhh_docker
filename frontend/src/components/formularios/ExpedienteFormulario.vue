<script setup>
import { computed, ref, watch } from 'vue'

import DialogoFormulario from '@/components/DialogoFormulario.vue'
import { useCarga, useGuardado } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'
import {
  ESTADOS_EXPEDIENTE,
  limpiarFormulario,
  nombreCompleto,
  reglas,
  ubicacionTexto
} from '@/utils/formato'

const abierto = defineModel({ type: Boolean, default: false })

const props = defineProps({
  expediente: { type: Object, default: null }
})

const emit = defineEmits(['guardado'])

const { guardando, error, guardar } = useGuardado()
const { cargando: cargandoOpciones, error: errorOpciones, ejecutar } = useCarga()

const formulario = ref(vacio())
const personal = ref([])
const ubicaciones = ref([])

const editando = computed(() => props.expediente !== null)

const opcionesPersonal = computed(() =>
  personal.value.map((persona) => ({
    value: persona.id_personal,
    title: nombreCompleto(persona),
    subtitle: [persona.numero_documento, persona.cargo, persona.estado_laboral]
      .filter(Boolean)
      .join(' · ')
  }))
)

// Solo ubicaciones activas; la actual se incluye aunque esté inactiva para que se vea.
const opcionesUbicacion = computed(() =>
  ubicaciones.value
    .filter((ubicacion) => ubicacion.estado || ubicacion.id_ubicacion === formulario.value.id_ubicacion)
    .map((ubicacion) => ({
      value: ubicacion.id_ubicacion,
      title: ubicacionTexto(ubicacion) + (ubicacion.estado ? '' : ' (inactiva)'),
      subtitle: ubicacion.descripcion ?? ''
    }))
)

function vacio() {
  return {
    numero_correlativo: '',
    id_personal: null,
    id_ubicacion: null,
    estado_expediente: 'COMPLETO',
    observaciones: ''
  }
}

watch(abierto, async (valor) => {
  if (!valor) {
    return
  }

  error.value = ''

  formulario.value = editando.value
    ? {
        numero_correlativo: props.expediente.numero_correlativo,
        id_personal: props.expediente.id_personal,
        id_ubicacion: props.expediente.id_ubicacion,
        estado_expediente: props.expediente.estado_expediente,
        observaciones: props.expediente.observaciones ?? ''
      }
    : vacio()

  await ejecutar(async () => {
    const [respuestaPersonal, respuestaUbicaciones] = await Promise.all([
      api.get('/personal'),
      api.get('/ubicaciones')
    ])

    personal.value = respuestaPersonal.personal
    ubicaciones.value = respuestaUbicaciones.ubicaciones
  })
})

async function enviar() {
  const datos = limpiarFormulario(formulario.value)
  let respuesta

  const exito = await guardar(async () => {
    respuesta = editando.value
      ? await api.put(`/expedientes/${props.expediente.id_expediente}`, datos)
      : await api.post('/expedientes', datos)
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
    :titulo="editando ? 'Editar expediente' : 'Registrar expediente'"
    :guardando="guardando"
    :error="error || errorOpciones"
    ancho="720"
    @guardar="enviar"
  >
    <v-row density="comfortable">
      <v-col cols="12" sm="5">
        <v-text-field
          v-model="formulario.numero_correlativo"
          label="N° correlativo *"
          :rules="[reglas.obligatorio, reglas.maximo(30)]"
        />
      </v-col>
      <v-col cols="12" sm="7">
        <v-select
          v-model="formulario.estado_expediente"
          :items="ESTADOS_EXPEDIENTE"
          label="Estado del expediente *"
          :rules="[reglas.obligatorio]"
        />
      </v-col>
      <v-col cols="12">
        <v-autocomplete
          v-model="formulario.id_personal"
          :items="opcionesPersonal"
          :loading="cargandoOpciones"
          label="Servidor o ex servidor público *"
          no-data-text="No se encontró personal. Regístrelo primero en Personal."
          :rules="[reglas.obligatorio]"
        >
          <template #item="{ props: propiedades, item }">
            <v-list-item v-bind="propiedades" :subtitle="item.subtitle" />
          </template>
        </v-autocomplete>
      </v-col>
      <v-col cols="12">
        <v-autocomplete
          v-model="formulario.id_ubicacion"
          :items="opcionesUbicacion"
          :loading="cargandoOpciones"
          label="Ubicación física"
          no-data-text="No hay ubicaciones activas."
          clearable
        />
      </v-col>
      <v-col cols="12">
        <v-textarea
          v-model="formulario.observaciones"
          label="Observaciones"
          hint="Faltantes, deterioro u otras observaciones del expediente."
        />
      </v-col>
    </v-row>
  </DialogoFormulario>
</template>

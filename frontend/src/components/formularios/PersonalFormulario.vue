<script setup>
import { computed, ref, watch } from 'vue'

import DialogoFormulario from '@/components/DialogoFormulario.vue'
import { useGuardado } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'
import { ESTADOS_LABORALES, limpiarFormulario, reglas } from '@/utils/formato'

const abierto = defineModel({ type: Boolean, default: false })

const props = defineProps({
  // Registro a editar; null para registrar uno nuevo.
  personal: { type: Object, default: null }
})

const emit = defineEmits(['guardado'])

const { guardando, error, guardar } = useGuardado()

const formulario = ref(vacio())

const editando = computed(() => props.personal !== null)

function vacio() {
  return {
    nombres: '',
    apellido_paterno: '',
    apellido_materno: '',
    numero_documento: '',
    cargo: '',
    estado_laboral: 'ACTIVO',
    fecha_ingreso: '',
    fecha_retiro: ''
  }
}

watch(abierto, (valor) => {
  if (!valor) {
    return
  }

  error.value = ''

  formulario.value = editando.value
    ? Object.fromEntries(
        Object.keys(vacio()).map((clave) => [clave, props.personal[clave] ?? ''])
      )
    : vacio()
})

const reglaFechaRetiro = (valor) =>
  !valor || !formulario.value.fecha_ingreso || valor >= formulario.value.fecha_ingreso ||
  'La fecha de retiro no puede ser anterior a la de ingreso.'

async function enviar() {
  const datos = limpiarFormulario(formulario.value)
  let respuesta

  const exito = await guardar(async () => {
    respuesta = editando.value
      ? await api.put(`/personal/${props.personal.id_personal}`, datos)
      : await api.post('/personal', datos)
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
    :titulo="editando ? 'Editar personal' : 'Registrar personal'"
    :guardando="guardando"
    :error="error"
    @guardar="enviar"
  >
    <v-row density="comfortable">
      <v-col cols="12">
        <v-text-field
          v-model="formulario.nombres"
          label="Nombres *"
          :rules="[reglas.obligatorio, reglas.maximo(100)]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.apellido_paterno"
          label="Apellido paterno"
          :rules="[reglas.maximo(100)]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.apellido_materno"
          label="Apellido materno"
          :rules="[reglas.maximo(100)]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.numero_documento"
          label="N° de documento (CI)"
          :rules="[reglas.maximo(30)]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-select
          v-model="formulario.estado_laboral"
          :items="ESTADOS_LABORALES"
          label="Condición *"
          :rules="[reglas.obligatorio]"
        />
      </v-col>
      <v-col cols="12">
        <v-text-field
          v-model="formulario.cargo"
          label="Cargo *"
          :rules="[reglas.obligatorio, reglas.maximo(150)]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field v-model="formulario.fecha_ingreso" label="Fecha de ingreso" type="date" />
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.fecha_retiro"
          label="Fecha de retiro"
          type="date"
          :rules="[reglaFechaRetiro]"
        />
      </v-col>
    </v-row>
  </DialogoFormulario>
</template>

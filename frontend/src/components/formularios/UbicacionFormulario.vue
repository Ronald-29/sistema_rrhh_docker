<script setup>
import { computed, ref, watch } from 'vue'

import DialogoFormulario from '@/components/DialogoFormulario.vue'
import { useGuardado } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'
import { limpiarFormulario, reglas } from '@/utils/formato'

const abierto = defineModel({ type: Boolean, default: false })

const props = defineProps({
  ubicacion: { type: Object, default: null }
})

const emit = defineEmits(['guardado'])

const { guardando, error, guardar } = useGuardado()

const formulario = ref(vacio())

const editando = computed(() => props.ubicacion !== null)

function vacio() {
  return {
    ambiente: '',
    estante: '',
    archivador: '',
    caja: '',
    carpeta: '',
    descripcion: ''
  }
}

watch(abierto, (valor) => {
  if (!valor) {
    return
  }

  error.value = ''

  formulario.value = editando.value
    ? Object.fromEntries(
        Object.keys(vacio()).map((clave) => [clave, props.ubicacion[clave] ?? ''])
      )
    : vacio()
})

async function enviar() {
  const datos = limpiarFormulario(formulario.value)
  let respuesta

  const exito = await guardar(async () => {
    respuesta = editando.value
      ? await api.put(`/ubicaciones/${props.ubicacion.id_ubicacion}`, datos)
      : await api.post('/ubicaciones', datos)
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
    :titulo="editando ? 'Editar ubicación' : 'Registrar ubicación'"
    :guardando="guardando"
    :error="error"
    @guardar="enviar"
  >
    <v-row density="comfortable">
      <v-col cols="12">
        <v-text-field
          v-model="formulario.ambiente"
          label="Ambiente *"
          hint="Por ejemplo: Archivo RRHH, Depósito planta baja"
          :rules="[reglas.obligatorio, reglas.maximo(100)]"
        />
      </v-col>
      <v-col cols="6" sm="3">
        <v-text-field v-model="formulario.estante" label="Estante" :rules="[reglas.maximo(50)]" />
      </v-col>
      <v-col cols="6" sm="3">
        <v-text-field v-model="formulario.archivador" label="Archivador" :rules="[reglas.maximo(50)]" />
      </v-col>
      <v-col cols="6" sm="3">
        <v-text-field v-model="formulario.caja" label="Caja" :rules="[reglas.maximo(50)]" />
      </v-col>
      <v-col cols="6" sm="3">
        <v-text-field v-model="formulario.carpeta" label="Carpeta" :rules="[reglas.maximo(50)]" />
      </v-col>
      <v-col cols="12">
        <v-text-field
          v-model="formulario.descripcion"
          label="Descripción"
          :rules="[reglas.maximo(200)]"
        />
      </v-col>
    </v-row>
  </DialogoFormulario>
</template>

<script setup>
import { ref, watch } from 'vue'

import DialogoFormulario from '@/components/DialogoFormulario.vue'
import { useGuardado } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'

const abierto = defineModel({ type: Boolean, default: false })

const { guardando, error, guardar } = useGuardado()

const formulario = ref(vacio())
const mostrarPassword = ref(false)

function vacio() {
  return {
    password_actual: '',
    password: '',
    repeticion: ''
  }
}

const reglaPassword = (valor) =>
  (valor ?? '').length >= 8 || 'La contraseña debe tener al menos 8 caracteres.'

const reglaRepeticion = (valor) =>
  valor === formulario.value.password || 'Las contraseñas no coinciden.'

watch(abierto, (valor) => {
  if (valor) {
    error.value = ''
    formulario.value = vacio()
    mostrarPassword.value = false
  }
})

async function enviar() {
  let respuesta

  const exito = await guardar(async () => {
    respuesta = await api.put('/mi-password', {
      password_actual: formulario.value.password_actual,
      password: formulario.value.password
    })
  })

  if (exito) {
    notificar.exito(respuesta.message)
    abierto.value = false
  }
}
</script>

<template>
  <DialogoFormulario
    v-model="abierto"
    titulo="Cambiar mi contraseña"
    texto-guardar="Cambiar contraseña"
    :guardando="guardando"
    :error="error"
    ancho="520"
    @guardar="enviar"
  >
    <v-text-field
      v-model="formulario.password_actual"
      label="Contraseña actual *"
      type="password"
      autocomplete="current-password"
      class="mb-2"
      :rules="[(valor) => !!valor || 'Debe indicar su contraseña actual.']"
    />

    <v-text-field
      v-model="formulario.password"
      label="Contraseña nueva *"
      :type="mostrarPassword ? 'text' : 'password'"
      :append-inner-icon="mostrarPassword ? 'mdi-eye-off' : 'mdi-eye'"
      autocomplete="new-password"
      hint="Mínimo 8 caracteres."
      class="mb-2"
      :rules="[reglaPassword]"
      @click:append-inner="mostrarPassword = !mostrarPassword"
    />

    <v-text-field
      v-model="formulario.repeticion"
      label="Repetir contraseña nueva *"
      :type="mostrarPassword ? 'text' : 'password'"
      autocomplete="new-password"
      :rules="[reglaRepeticion]"
    />
  </DialogoFormulario>
</template>

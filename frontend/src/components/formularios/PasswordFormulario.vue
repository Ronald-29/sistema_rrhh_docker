<script setup>
import { ref, watch } from 'vue'

import DialogoFormulario from '@/components/DialogoFormulario.vue'
import { useGuardado } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'

const abierto = defineModel({ type: Boolean, default: false })

const props = defineProps({
  usuario: { type: Object, required: true }
})

const emit = defineEmits(['guardado'])

const { guardando, error, guardar } = useGuardado()

const password = ref('')
const mostrarPassword = ref(false)

const reglaPassword = (valor) =>
  (valor ?? '').length >= 8 || 'La contraseña debe tener al menos 8 caracteres.'

watch([abierto, () => props.usuario.id_usuario], ([valor]) => {
  if (!valor) {
    return
  }

  error.value = ''
  password.value = ''
  mostrarPassword.value = false
}, { immediate: true })

async function enviar() {
  let respuesta

  const exito = await guardar(async () => {
    respuesta = await api.put(`/usuarios/${props.usuario.id_usuario}/password`, {
      password: password.value
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
    titulo="Asignar contraseña nueva"
    texto-guardar="Asignar contraseña"
    :guardando="guardando"
    :error="error"
    ancho="520"
    @guardar="enviar"
  >
    <v-alert type="info" variant="tonal" density="compact" class="mb-4">
      Usuario <b>{{ usuario.nombre_usuario }}</b> ({{ usuario.nombres }} {{ usuario.apellidos }}).
      Entréguele la contraseña para que la cambie después desde su propio menú.
    </v-alert>

    <v-text-field
      v-model="password"
      label="Contraseña nueva *"
      :type="mostrarPassword ? 'text' : 'password'"
      :append-inner-icon="mostrarPassword ? 'mdi-eye-off' : 'mdi-eye'"
      autocomplete="new-password"
      hint="Mínimo 8 caracteres."
      :rules="[reglaPassword]"
      @click:append-inner="mostrarPassword = !mostrarPassword"
    />
  </DialogoFormulario>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { iniciarSesion } from '@/services/sesion'
import { reglas } from '@/utils/formato'

const route = useRoute()
const router = useRouter()

const formulario = ref(null)
const nombreUsuario = ref('')
const password = ref('')
const mostrarPassword = ref(false)
const ingresando = ref(false)
const error = ref('')

async function ingresar() {
  const { valid } = await formulario.value.validate()

  if (!valid) {
    return
  }

  ingresando.value = true
  error.value = ''

  try {
    await iniciarSesion(nombreUsuario.value.trim(), password.value)

    const destino = typeof route.query.redirigir === 'string' && route.query.redirigir.startsWith('/')
      ? route.query.redirigir
      : { name: 'inicio' }

    router.replace(destino)
  } catch (excepcion) {
    error.value = excepcion.message
  } finally {
    ingresando.value = false
  }
}
</script>

<template>
  <v-main class="bg-background">
    <div class="contenedor-login">
      <v-card width="100%" max-width="420" class="pa-2" elevation="3">
        <v-card-item class="text-center pt-6">
          <v-icon icon="mdi-folder-account-outline" size="48" color="primary" />
          <v-card-title class="text-headline-small mt-2">Archivo de RRHH</v-card-title>
          <v-card-subtitle class="subtitulo">Empresa de Apoyo a la Producción de Alimentos - EMAPA</v-card-subtitle>
        </v-card-item>

        <v-card-text class="pt-4">
          <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4">
            {{ error }}
          </v-alert>

          <v-form ref="formulario" @submit.prevent="ingresar">
            <v-text-field
              v-model="nombreUsuario"
              label="Usuario"
              prepend-inner-icon="mdi-account"
              autocomplete="username"
              autofocus
              :rules="[reglas.obligatorio]"
              class="mb-2"
            />

            <v-text-field
              v-model="password"
              label="Contraseña"
              prepend-inner-icon="mdi-lock"
              :type="mostrarPassword ? 'text' : 'password'"
              :append-inner-icon="mostrarPassword ? 'mdi-eye-off' : 'mdi-eye'"
              autocomplete="current-password"
              :rules="[reglas.obligatorio]"
              class="mb-2"
              @click:append-inner="mostrarPassword = !mostrarPassword"
            />

            <v-btn type="submit" color="primary" size="large" block :loading="ingresando">
              Ingresar
            </v-btn>
          </v-form>
        </v-card-text>
      </v-card>
    </div>
  </v-main>
</template>

<style scoped>
.contenedor-login {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 16px;
}

.subtitulo {
  white-space: normal;
}
</style>

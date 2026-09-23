<script setup>
import { ref } from 'vue'

const abierto = defineModel({ type: Boolean, default: false })

defineProps({
  titulo: { type: String, required: true },
  guardando: { type: Boolean, default: false },
  error: { type: String, default: '' },
  textoGuardar: { type: String, default: 'Guardar' },
  colorGuardar: { type: String, default: 'primary' },
  ancho: { type: [Number, String], default: 640 }
})

const emit = defineEmits(['guardar'])

const formulario = ref(null)

async function enviar() {
  const { valid } = await formulario.value.validate()

  if (valid) {
    emit('guardar')
  }
}
</script>

<template>
  <v-dialog v-model="abierto" :max-width="ancho" persistent>
    <v-form ref="formulario" @submit.prevent="enviar">
      <v-card>
        <v-card-title class="pt-5 px-6 text-title-large">{{ titulo }}</v-card-title>

        <v-card-text class="px-6">
          <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4">
            {{ error }}
          </v-alert>

          <slot />
        </v-card-text>

        <v-card-actions class="px-6 pb-5">
          <v-spacer />
          <v-btn variant="text" :disabled="guardando" @click="abierto = false">Cancelar</v-btn>
          <v-btn :color="colorGuardar" variant="flat" type="submit" :loading="guardando">
            {{ textoGuardar }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>
  </v-dialog>
</template>

<script setup>
import { computed } from 'vue'

import { buscarEnCatalogo } from '@/utils/formato'

const props = defineProps({
  catalogo: { type: Array, required: true },
  valor: { type: String, default: null }
})

const estado = computed(() => buscarEnCatalogo(props.catalogo, props.valor))
</script>

<template>
  <v-chip :color="estado.color" size="small" variant="tonal" label>
    {{ estado.title }}
  </v-chip>
</template>

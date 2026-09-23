<script setup>
defineProps({
  titulo: { type: String, default: '' },
  subtitulo: { type: String, default: '' }
})
</script>

<template>
  <div class="d-flex flex-wrap align-center ga-3 mb-4">
    <div class="flex-grow-1">
      <h1 v-if="titulo" class="text-headline-small">{{ titulo }}</h1>
      <p v-if="subtitulo" class="text-body-medium text-medium-emphasis">{{ subtitulo }}</p>
      <slot name="detalle" />
    </div>

    <div class="d-flex flex-wrap ga-2">
      <slot name="acciones" />
    </div>
  </div>
</template>

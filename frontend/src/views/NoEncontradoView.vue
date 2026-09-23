<template>
  <v-empty-state
    icon="mdi-map-marker-question-outline"
    headline="Página no encontrada"
    text="La dirección que abrió no existe en el sistema."
  >
    <template #actions>
      <v-btn color="primary" :to="{ name: 'inicio' }">Ir al inicio</v-btn>
    </template>
  </v-empty-state>
</template>

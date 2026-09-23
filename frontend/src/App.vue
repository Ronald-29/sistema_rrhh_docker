<script setup>
import { notificacion } from '@/services/notificaciones'
</script>

<template>
  <v-app>
    <router-view />

    <v-snackbar
      v-model="notificacion.visible"
      :color="notificacion.color"
      :timeout="notificacion.color === 'error' ? 7000 : 4000"
      location="top right"
    >
      {{ notificacion.mensaje }}

      <template #actions>
        <v-btn variant="text" @click="notificacion.visible = false">Cerrar</v-btn>
      </template>
    </v-snackbar>
  </v-app>
</template>

<style>
/* Enlaces dentro de tablas y textos (router-link) con el color del tema. */
.v-main a:not(.v-btn):not(.v-list-item):not(.v-card) {
  color: rgb(var(--v-theme-primary));
  font-weight: 500;
  text-decoration: none;
}

.v-main a:not(.v-btn):not(.v-list-item):not(.v-card):hover {
  text-decoration: underline;
}
</style>

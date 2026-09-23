<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'

import MiPasswordFormulario from '@/components/formularios/MiPasswordFormulario.vue'
import { cerrarSesion, sesion } from '@/services/sesion'

const router = useRouter()
const { mdAndUp } = useDisplay()

const menuAbierto = ref(null)

const opciones = computed(() => [
  { titulo: 'Inicio', icono: 'mdi-view-dashboard-outline', ruta: { name: 'inicio' } },
  { titulo: 'Expedientes', icono: 'mdi-folder-account-outline', ruta: { name: 'expedientes' } },
  { titulo: 'Personal', icono: 'mdi-account-group-outline', ruta: { name: 'personal' } },
  { titulo: 'Ubicaciones', icono: 'mdi-archive-outline', ruta: { name: 'ubicaciones' } },
  { titulo: 'Préstamos', icono: 'mdi-swap-horizontal', ruta: { name: 'prestamos' } },
  { titulo: 'Reportes', icono: 'mdi-file-chart-outline', ruta: { name: 'reportes' } },
  ...(sesion.esAdministrador
    ? [
        { titulo: 'Usuarios', icono: 'mdi-account-key-outline', ruta: { name: 'usuarios' } },
        { titulo: 'Auditoría', icono: 'mdi-shield-search', ruta: { name: 'auditoria' } }
      ]
    : [])
])

const dialogoMiPassword = ref(false)

async function salir() {
  await cerrarSesion()
  router.push({ name: 'login' })
}
</script>

<template>
  <v-navigation-drawer
    v-model="menuAbierto"
    :permanent="mdAndUp"
    color="primary"
  >
    <div class="pa-4">
      <div class="text-title-large font-weight-bold">Archivo RRHH</div>
      <div class="text-body-small">EMAPA</div>
    </div>

    <v-divider />

    <v-list nav density="comfortable">
      <v-list-item
        v-for="opcion in opciones"
        :key="opcion.titulo"
        :to="opcion.ruta"
        :prepend-icon="opcion.icono"
        :title="opcion.titulo"
        :exact="opcion.ruta.name === 'inicio'"
      />
    </v-list>
  </v-navigation-drawer>

  <v-app-bar flat border="b">
    <v-app-bar-nav-icon v-if="!mdAndUp" @click="menuAbierto = !menuAbierto" />

    <v-app-bar-title>{{ $route.meta.titulo }}</v-app-bar-title>

    <template #append>
      <v-menu>
        <template #activator="{ props }">
          <v-btn v-bind="props" variant="text" prepend-icon="mdi-account-circle">
            <span class="d-none d-sm-inline">{{ sesion.nombreVisible }}</span>
          </v-btn>
        </template>

        <v-list min-width="240">
          <v-list-item
            :title="sesion.nombreVisible"
            :subtitle="sesion.usuario?.rol"
          />
          <v-divider />
          <v-list-item
            prepend-icon="mdi-lock-reset"
            title="Cambiar mi contraseña"
            @click="dialogoMiPassword = true"
          />
          <v-list-item prepend-icon="mdi-logout" title="Cerrar sesión" @click="salir" />
        </v-list>
      </v-menu>
    </template>
  </v-app-bar>

  <MiPasswordFormulario v-model="dialogoMiPassword" />

  <v-main>
    <v-container fluid class="pa-4 pa-md-6">
      <router-view />
    </v-container>
  </v-main>
</template>

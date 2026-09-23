<script setup>
import { computed, onMounted, ref } from 'vue'

import EncabezadoPagina from '@/components/EncabezadoPagina.vue'
import { useCarga } from '@/composables/useCarga'
import { api } from '@/services/api'
import { sesion } from '@/services/sesion'

const { cargando, error, ejecutar } = useCarga()

const inventario = ref(null)
const prestamos = ref(null)

const porcentajeRevision = computed(() => {
  const total = inventario.value?.total_expedientes ?? 0

  return total > 0 ? Math.round((inventario.value.total_revisados * 100) / total) : 0
})

const tarjetas = computed(() => [
  {
    titulo: 'Expedientes',
    valor: inventario.value?.total_expedientes,
    icono: 'mdi-folder-multiple-outline',
    color: 'primary',
    ruta: { name: 'expedientes' }
  },
  {
    titulo: 'Archivos activos',
    valor: inventario.value?.total_activos,
    icono: 'mdi-account-check-outline',
    color: 'success',
    ruta: { name: 'expedientes', query: { estado_laboral: 'ACTIVO' } }
  },
  {
    titulo: 'Archivos pasivos',
    valor: inventario.value?.total_pasivos,
    icono: 'mdi-account-clock-outline',
    color: 'blue-grey',
    ruta: { name: 'expedientes', query: { estado_laboral: 'PASIVO' } }
  },
  {
    titulo: 'Préstamos pendientes',
    valor: prestamos.value?.total_pendientes,
    icono: 'mdi-swap-horizontal',
    color: 'warning',
    ruta: { name: 'prestamos', query: { estado: 'PRESTADO' } }
  }
])

const estadosExpediente = computed(() => [
  { titulo: 'Completos', valor: inventario.value?.total_completos, color: 'success', estado: 'COMPLETO' },
  { titulo: 'Incompletos', valor: inventario.value?.total_incompletos, color: 'warning', estado: 'INCOMPLETO' },
  { titulo: 'Deteriorados', valor: inventario.value?.total_deteriorados, color: 'error', estado: 'DETERIORADO' },
  { titulo: 'Observados', valor: inventario.value?.total_observados, color: 'info', estado: 'OBSERVADO' }
])

async function cargar() {
  await ejecutar(async () => {
    const [respuestaInventario, respuestaPrestamos] = await Promise.all([
      api.get('/reportes/inventario/resumen'),
      api.get('/reportes/prestamos/resumen')
    ])

    inventario.value = respuestaInventario.resumen
    prestamos.value = respuestaPrestamos.resumen
  })
}

onMounted(cargar)
</script>

<template>
  <EncabezadoPagina
    :titulo="`Bienvenido, ${sesion.nombreVisible}`"
    subtitulo="Resumen del inventario de expedientes personales del Archivo de Recursos Humanos."
  />

  <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
    {{ error }}
    <template #append>
      <v-btn variant="text" @click="cargar">Reintentar</v-btn>
    </template>
  </v-alert>

  <v-row>
    <v-col v-for="tarjeta in tarjetas" :key="tarjeta.titulo" cols="12" sm="6" lg="3">
      <v-card :to="tarjeta.ruta" :loading="cargando" height="100%">
        <v-card-text class="d-flex align-center ga-4">
          <v-avatar :color="tarjeta.color" variant="tonal" size="56" rounded="lg">
            <v-icon :icon="tarjeta.icono" size="28" />
          </v-avatar>
          <div>
            <div class="text-headline-medium font-weight-bold">{{ tarjeta.valor ?? '—' }}</div>
            <div class="text-body-medium text-medium-emphasis">{{ tarjeta.titulo }}</div>
          </div>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col cols="12" md="6">
      <v-card height="100%">
        <v-card-item title="Avance de la revisión física" prepend-icon="mdi-clipboard-check-outline" />
        <v-card-text>
          <div class="d-flex align-end justify-space-between mb-2">
            <span class="text-display-small font-weight-bold">{{ porcentajeRevision }}%</span>
            <span class="text-body-medium text-medium-emphasis">
              {{ inventario?.total_revisados ?? 0 }} de {{ inventario?.total_expedientes ?? 0 }} expedientes revisados
            </span>
          </div>
          <v-progress-linear :model-value="porcentajeRevision" color="secondary" height="12" rounded />
          <v-btn
            class="mt-4"
            variant="tonal"
            color="primary"
            prepend-icon="mdi-format-list-checks"
            :to="{ name: 'expedientes', query: { revisado: 'false' } }"
          >
            Ver pendientes de revisión ({{ inventario?.total_pendientes_revision ?? 0 }})
          </v-btn>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col cols="12" md="6">
      <v-card height="100%">
        <v-card-item title="Estado de los expedientes" prepend-icon="mdi-folder-alert-outline" />
        <v-list density="comfortable">
          <v-list-item
            v-for="estado in estadosExpediente"
            :key="estado.estado"
            :title="estado.titulo"
            :to="{ name: 'expedientes', query: { estado_expediente: estado.estado } }"
          >
            <template #prepend>
              <v-badge dot inline :color="estado.color" class="me-3" />
            </template>
            <template #append>
              <span class="text-title-medium">{{ estado.valor ?? '—' }}</span>
            </template>
          </v-list-item>
          <v-list-item title="Sin ubicación asignada">
            <template #prepend>
              <v-badge dot inline color="grey" class="me-3" />
            </template>
            <template #append>
              <span class="text-title-medium">{{ inventario?.total_sin_ubicacion ?? '—' }}</span>
            </template>
          </v-list-item>
        </v-list>
      </v-card>
    </v-col>
  </v-row>
</template>

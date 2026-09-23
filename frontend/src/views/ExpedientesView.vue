<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import ChipEstado from '@/components/ChipEstado.vue'
import EncabezadoPagina from '@/components/EncabezadoPagina.vue'
import ExpedienteFormulario from '@/components/formularios/ExpedienteFormulario.vue'
import { useCarga } from '@/composables/useCarga'
import { api } from '@/services/api'
import { sesion } from '@/services/sesion'
import {
  ESTADOS_EXPEDIENTE,
  ESTADOS_LABORALES,
  nombreCompleto,
  ubicacionTexto
} from '@/utils/formato'

const route = useRoute()
const router = useRouter()
const { cargando, error, ejecutar } = useCarga()

const expedientes = ref([])
const dialogoAbierto = ref(false)

const opcionesRevision = [
  { title: 'Revisados', value: 'true' },
  { title: 'Pendientes de revisión', value: 'false' }
]

// Los filtros pueden venir en la URL, por ejemplo desde las tarjetas del inicio.
const filtros = ref({
  buscar: '',
  estadoLaboral: route.query.estado_laboral ?? null,
  estadoExpediente: route.query.estado_expediente ?? null,
  revisado: route.query.revisado ?? null
})

const encabezados = [
  { title: 'N° correlativo', key: 'numero_correlativo', sortRaw: (a, b) => compararNatural(a.numero_correlativo, b.numero_correlativo) },
  { title: 'Nombre completo', key: 'nombre_completo' },
  { title: 'Condición', key: 'estado_laboral' },
  { title: 'Cargo', key: 'cargo' },
  { title: 'Ubicación física', key: 'ubicacion' },
  { title: 'Estado', key: 'estado_expediente' },
  { title: 'Revisión', key: 'revisado', align: 'center' }
]

const filas = computed(() =>
  expedientes.value
    .filter((expediente) =>
      (!filtros.value.estadoLaboral || expediente.estado_laboral === filtros.value.estadoLaboral) &&
      (!filtros.value.estadoExpediente || expediente.estado_expediente === filtros.value.estadoExpediente) &&
      (!filtros.value.revisado || String(expediente.revisado) === filtros.value.revisado)
    )
    .map((expediente) => ({
      ...expediente,
      nombre_completo: nombreCompleto(expediente),
      ubicacion: expediente.id_ubicacion ? ubicacionTexto(expediente) : ''
    }))
)

function compararNatural(a, b) {
  return String(a).localeCompare(String(b), 'es', { numeric: true, sensitivity: 'base' })
}

async function cargar() {
  await ejecutar(async () => {
    expedientes.value = (await api.get('/expedientes')).expedientes
  })
}

function verDetalle(_evento, { item }) {
  router.push({ name: 'expediente', params: { id: item.id_expediente } })
}

function alGuardar(respuesta) {
  if (respuesta.id_expediente) {
    router.push({ name: 'expediente', params: { id: respuesta.id_expediente } })
  }
}

onMounted(cargar)
</script>

<template>
  <EncabezadoPagina subtitulo="Expedientes personales del archivo. Haga clic en una fila para ver el detalle.">
    <template #acciones>
      <v-btn v-if="sesion.puedeEditar" color="primary" prepend-icon="mdi-plus" @click="dialogoAbierto = true">
        Registrar expediente
      </v-btn>
    </template>
  </EncabezadoPagina>

  <v-card>
    <v-card-text>
      <v-row density="comfortable">
        <v-col cols="12" lg="4">
          <v-text-field
            v-model="filtros.buscar"
            prepend-inner-icon="mdi-magnify"
            label="Buscar por correlativo, nombre, cargo o ubicación"
            clearable
            hide-details
          />
        </v-col>
        <v-col cols="12" sm="4" lg="2">
          <v-select
            v-model="filtros.estadoLaboral"
            :items="ESTADOS_LABORALES"
            label="Condición"
            clearable
            hide-details
          />
        </v-col>
        <v-col cols="12" sm="4" lg="3">
          <v-select
            v-model="filtros.estadoExpediente"
            :items="ESTADOS_EXPEDIENTE"
            label="Estado del expediente"
            clearable
            hide-details
          />
        </v-col>
        <v-col cols="12" sm="4" lg="3">
          <v-select
            v-model="filtros.revisado"
            :items="opcionesRevision"
            label="Revisión física"
            clearable
            hide-details
          />
        </v-col>
      </v-row>
    </v-card-text>

    <v-alert v-if="error" type="error" variant="tonal" class="mx-4 mb-4">
      {{ error }}
      <template #append>
        <v-btn variant="text" @click="cargar">Reintentar</v-btn>
      </template>
    </v-alert>

    <v-data-table
      :headers="encabezados"
      :items="filas"
      :search="filtros.buscar"
      :loading="cargando"
      item-value="id_expediente"
      class="tabla-clic"
      loading-text="Cargando expedientes..."
      no-data-text="No hay expedientes que coincidan con los filtros."
      @click:row="verDetalle"
    >
      <template #item.numero_correlativo="{ item }">
        <span class="font-weight-medium">{{ item.numero_correlativo }}</span>
      </template>

      <template #item.estado_laboral="{ item }">
        <ChipEstado :catalogo="ESTADOS_LABORALES" :valor="item.estado_laboral" />
      </template>

      <template #item.ubicacion="{ item }">
        <span v-if="item.ubicacion">{{ item.ubicacion }}</span>
        <span v-else class="text-medium-emphasis">Sin ubicación</span>
      </template>

      <template #item.estado_expediente="{ item }">
        <ChipEstado :catalogo="ESTADOS_EXPEDIENTE" :valor="item.estado_expediente" />
      </template>

      <template #item.revisado="{ item }">
        <v-icon
          :icon="item.revisado ? 'mdi-check-circle' : 'mdi-clock-outline'"
          :color="item.revisado ? 'success' : 'grey'"
          :title="item.revisado ? `Revisado por ${item.revisado_por}` : 'Pendiente de revisión'"
        />
      </template>
    </v-data-table>
  </v-card>

  <ExpedienteFormulario v-model="dialogoAbierto" @guardado="alGuardar" />
</template>

<style scoped>
.tabla-clic :deep(tbody tr) {
  cursor: pointer;
}
</style>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import ChipEstado from '@/components/ChipEstado.vue'
import EncabezadoPagina from '@/components/EncabezadoPagina.vue'
import DevolucionFormulario from '@/components/formularios/DevolucionFormulario.vue'
import PrestamoFormulario from '@/components/formularios/PrestamoFormulario.vue'
import { useCarga } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'
import { sesion } from '@/services/sesion'
import { ESTADOS_PRESTAMO, fechaHora } from '@/utils/formato'

const route = useRoute()
const { cargando, error, ejecutar } = useCarga()

const prestamos = ref([])
const buscar = ref('')
const estado = ref(route.query.estado ?? null)
const dialogoPrestamo = ref(false)
const dialogoDevolucion = ref(false)
const prestamoSeleccionado = ref(null)
const detalle = ref(null)
const cargandoDetalle = ref(null)

const encabezados = computed(() => [
  { title: 'N°', key: 'id_prestamo' },
  { title: 'Préstamo de', key: 'objeto' },
  { title: 'Entregado por', key: 'entregado_por' },
  { title: 'Recibido por', key: 'recibido_por' },
  { title: 'Fecha de entrega', key: 'fecha_hora_entrega' },
  { title: 'Devolución', key: 'devolucion', sortable: false },
  { title: 'Estado', key: 'estado' },
  { title: '', key: 'acciones', sortable: false, align: 'end' }
])

const filas = computed(() =>
  prestamos.value
    .filter((prestamo) => !estado.value || prestamo.estado === estado.value)
    .map((prestamo) => ({
      ...prestamo,
      objeto: prestamo.id_documento
        ? `Documento: ${prestamo.nombre_documento ?? prestamo.id_documento}`
        : `Expediente N° ${prestamo.numero_correlativo}`
    }))
)

async function cargar() {
  await ejecutar(async () => {
    prestamos.value = (await api.get('/prestamos')).prestamos
  })
}

function devolver(prestamo) {
  prestamoSeleccionado.value = prestamo
  dialogoDevolucion.value = true
}

async function verDetalle(prestamo) {
  cargandoDetalle.value = prestamo.id_prestamo

  try {
    detalle.value = { ...prestamo, ...(await api.get(`/prestamos/${prestamo.id_prestamo}`)).prestamo }
  } catch (excepcion) {
    notificar.error(excepcion)
  } finally {
    cargandoDetalle.value = null
  }
}

onMounted(cargar)
</script>

<template>
  <EncabezadoPagina subtitulo="Registro de quién entrega y quién recibe cada expediente o documento, y de su devolución.">
    <template #acciones>
      <v-btn v-if="sesion.puedeEditar" color="primary" prepend-icon="mdi-plus" @click="dialogoPrestamo = true">
        Registrar préstamo
      </v-btn>
    </template>
  </EncabezadoPagina>

  <v-card>
    <v-card-text>
      <v-row density="comfortable">
        <v-col cols="12" md="8">
          <v-text-field
            v-model="buscar"
            prepend-inner-icon="mdi-magnify"
            label="Buscar por expediente, documento o persona"
            clearable
            hide-details
          />
        </v-col>
        <v-col cols="12" md="4">
          <v-select v-model="estado" :items="ESTADOS_PRESTAMO" label="Estado" clearable hide-details />
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
      :search="buscar"
      :loading="cargando"
      item-value="id_prestamo"
      loading-text="Cargando préstamos..."
      no-data-text="No hay préstamos que coincidan con los filtros."
    >
      <template #item.objeto="{ item }">
        <router-link
          v-if="item.id_expediente"
          :to="{ name: 'expediente', params: { id: item.id_expediente } }"
        >
          {{ item.objeto }}
        </router-link>
        <span v-else>{{ item.objeto }}</span>
      </template>

      <template #item.fecha_hora_entrega="{ item }">{{ fechaHora(item.fecha_hora_entrega) }}</template>

      <template #item.devolucion="{ item }">
        <template v-if="item.estado === 'DEVUELTO'">
          <div>{{ item.devuelto_por }} → {{ item.recibido_devolucion_por }}</div>
          <div class="text-body-small text-medium-emphasis">{{ fechaHora(item.fecha_hora_devolucion) }}</div>
        </template>
        <span v-else class="text-medium-emphasis">Pendiente</span>
      </template>

      <template #item.estado="{ item }">
        <ChipEstado :catalogo="ESTADOS_PRESTAMO" :valor="item.estado" />
      </template>

      <template #item.acciones="{ item }">
        <div class="d-flex justify-end align-center">
          <v-btn
            v-if="item.estado === 'PRESTADO' && sesion.puedeEditar"
            variant="text"
            color="success"
            size="small"
            @click="devolver(item)"
          >
            Devolver
          </v-btn>
          <v-btn
            icon="mdi-eye-outline"
            variant="text"
            size="small"
            title="Ver detalle"
            aria-label="Ver detalle"
            :loading="cargandoDetalle === item.id_prestamo"
            @click="verDetalle(item)"
          />
        </div>
      </template>
    </v-data-table>
  </v-card>

  <PrestamoFormulario v-model="dialogoPrestamo" @guardado="cargar" />

  <DevolucionFormulario
    v-if="prestamoSeleccionado"
    v-model="dialogoDevolucion"
    :prestamo="prestamoSeleccionado"
    @guardado="cargar"
  />

  <v-dialog :model-value="detalle !== null" max-width="600" @update:model-value="detalle = null">
    <v-card v-if="detalle">
      <v-card-title class="pt-5 px-6 d-flex align-center ga-2">
        <span class="text-title-large">Préstamo N° {{ detalle.id_prestamo }}</span>
        <ChipEstado :catalogo="ESTADOS_PRESTAMO" :valor="detalle.estado" />
      </v-card-title>
      <v-card-text class="px-6">
        <p class="text-title-medium mb-3">{{ detalle.objeto }}</p>
        <v-table density="compact">
          <tbody>
            <tr><th colspan="2" class="bg-surface-variant">Entrega</th></tr>
            <tr><th>Entregado por</th><td>{{ detalle.entregado_por }}</td></tr>
            <tr><th>Recibido por</th><td>{{ detalle.recibido_por }}</td></tr>
            <tr><th>Fecha y hora</th><td>{{ fechaHora(detalle.fecha_hora_entrega) }}</td></tr>
            <tr><th>Motivo</th><td>{{ detalle.motivo || '—' }}</td></tr>
            <tr><th>Observaciones</th><td>{{ detalle.observaciones_entrega || '—' }}</td></tr>
            <tr><th>Registrado por</th><td>{{ detalle.usuario_registro }}</td></tr>
            <tr><th colspan="2" class="bg-surface-variant">Devolución</th></tr>
            <template v-if="detalle.estado === 'DEVUELTO'">
              <tr><th>Devuelto por</th><td>{{ detalle.devuelto_por }}</td></tr>
              <tr><th>Recibido por</th><td>{{ detalle.recibido_devolucion_por }}</td></tr>
              <tr><th>Fecha y hora</th><td>{{ fechaHora(detalle.fecha_hora_devolucion) }}</td></tr>
              <tr><th>Observaciones</th><td>{{ detalle.observaciones_devolucion || '—' }}</td></tr>
            </template>
            <tr v-else><td colspan="2" class="text-medium-emphasis">Pendiente de devolución</td></tr>
          </tbody>
        </v-table>
      </v-card-text>
      <v-card-actions class="px-6 pb-5">
        <v-spacer />
        <v-btn variant="text" @click="detalle = null">Cerrar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

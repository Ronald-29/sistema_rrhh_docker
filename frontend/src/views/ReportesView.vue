<script setup>
import { computed, onMounted, ref } from 'vue'

import ChipEstado from '@/components/ChipEstado.vue'
import EncabezadoPagina from '@/components/EncabezadoPagina.vue'
import { useCarga } from '@/composables/useCarga'
import { api, urlApi } from '@/services/api'
import {
  ESTADOS_EXPEDIENTE,
  ESTADOS_LABORALES,
  fechaHora,
  nombreCompleto,
  periodoLaboral,
  ubicacionTexto
} from '@/utils/formato'

const { cargando, error, ejecutar } = useCarga()

const pestana = ref('inventario')
const inventario = ref([])
const pendientes = ref([])
const ubicaciones = ref([])

const filtros = ref({ buscar: '', estadoLaboral: null, estadoExpediente: null })

const rotulos = ref({ tipo: 'carpeta', estadoLaboral: null, idUbicacion: null })

const tiposRotulo = [
  { title: 'Carpetas (un rótulo por expediente)', value: 'carpeta' },
  { title: 'Cajas', value: 'caja' },
  { title: 'Archivadores', value: 'archivador' }
]

const opcionesUbicacion = computed(() =>
  ubicaciones.value.map((ubicacion) => ({
    value: ubicacion.id_ubicacion,
    title: ubicacionTexto(ubicacion)
  }))
)

const urlRotulos = computed(() =>
  urlApi('/reportes/rotulos', {
    tipo: rotulos.value.tipo,
    estado_laboral: rotulos.value.estadoLaboral,
    id_ubicacion: rotulos.value.idUbicacion
  })
)

const encabezadosInventario = [
  { title: 'N° correlativo', key: 'numero_correlativo' },
  { title: 'Nombre completo', key: 'nombre_completo' },
  { title: 'N° documento', key: 'numero_documento' },
  { title: 'Condición', key: 'estado_laboral' },
  { title: 'Cargo', key: 'cargo' },
  { title: 'Gestión o periodo', key: 'periodo', sortable: false },
  { title: 'Ubicación física', key: 'ubicacion' },
  { title: 'Estado', key: 'estado_expediente' },
  { title: 'Observaciones', key: 'observaciones', sortable: false }
]

const encabezadosPendientes = [
  { title: 'Préstamo de', key: 'objeto', sortable: false },
  { title: 'Servidor o ex servidor', key: 'nombre_completo' },
  { title: 'Entregado por', key: 'entregado_por' },
  { title: 'Recibido por', key: 'recibido_por' },
  { title: 'Fecha de entrega', key: 'fecha_hora_entrega' },
  { title: 'Días', key: 'dias', align: 'center' },
  { title: 'Motivo', key: 'motivo', sortable: false }
]

const filasInventario = computed(() =>
  inventario.value
    .filter((fila) =>
      (!filtros.value.estadoLaboral || fila.estado_laboral === filtros.value.estadoLaboral) &&
      (!filtros.value.estadoExpediente || fila.estado_expediente === filtros.value.estadoExpediente)
    )
    .map((fila) => ({
      ...fila,
      nombre_completo: nombreCompleto(fila),
      periodo: periodoLaboral(fila),
      ubicacion: fila.id_ubicacion ? ubicacionTexto(fila) : 'Sin ubicación asignada'
    }))
)

const filasPendientes = computed(() =>
  pendientes.value.map((prestamo) => ({
    ...prestamo,
    nombre_completo: nombreCompleto(prestamo),
    objeto: prestamo.tipo_prestamo === 'DOCUMENTO'
      ? `Documento: ${prestamo.nombre_documento} (exp. ${prestamo.numero_correlativo})`
      : `Expediente N° ${prestamo.numero_correlativo}`,
    dias: diasDesde(prestamo.fecha_hora_entrega)
  }))
)

function diasDesde(fechaHoraTexto) {
  const inicio = new Date(String(fechaHoraTexto).replace(' ', 'T'))

  return Number.isNaN(inicio.getTime())
    ? null
    : Math.max(0, Math.floor((Date.now() - inicio.getTime()) / 86400000))
}

async function cargar() {
  await ejecutar(async () => {
    const [respuestaInventario, respuestaPendientes, respuestaUbicaciones] = await Promise.all([
      api.get('/reportes/inventario'),
      api.get('/reportes/prestamos/pendientes'),
      api.get('/ubicaciones')
    ])

    inventario.value = respuestaInventario.inventario
    pendientes.value = respuestaPendientes.prestamos_pendientes
    ubicaciones.value = respuestaUbicaciones.ubicaciones
  })
}

onMounted(cargar)
</script>

<template>
  <EncabezadoPagina subtitulo="Inventario consolidado, informe final y rótulos para el trabajo de archivo." />

  <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
    {{ error }}
    <template #append>
      <v-btn variant="text" @click="cargar">Reintentar</v-btn>
    </template>
  </v-alert>

  <v-row class="mb-2">
    <v-col cols="12" md="6">
      <v-card height="100%">
        <v-card-item
          title="Informe final"
          subtitle="Inventario consolidado de activos y pasivos con sus observaciones."
          prepend-icon="mdi-file-document-check-outline"
        />
        <v-card-text class="d-flex flex-wrap ga-2">
          <v-btn
            color="success"
            prepend-icon="mdi-microsoft-excel"
            :href="urlApi('/reportes/inventario/excel')"
          >
            Descargar Excel
          </v-btn>
          <v-btn
            color="primary"
            variant="tonal"
            prepend-icon="mdi-printer-outline"
            :href="urlApi('/reportes/informe-final')"
            target="_blank"
          >
            Abrir informe imprimible
          </v-btn>
          <p class="text-body-small text-medium-emphasis w-100 mt-2">
            El informe se abre en otra pestaña. Para obtener un PDF use Imprimir y elija
            "Guardar como PDF".
          </p>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col cols="12" md="6">
      <v-card height="100%">
        <v-card-item
          title="Rótulos"
          subtitle="Para carpetas, cajas y archivadores."
          prepend-icon="mdi-label-multiple-outline"
        />
        <v-card-text>
          <v-row density="comfortable">
            <v-col cols="12" sm="6">
              <v-select v-model="rotulos.tipo" :items="tiposRotulo" label="Tipo de rótulo" hide-details />
            </v-col>
            <v-col cols="12" sm="6">
              <v-select
                v-model="rotulos.estadoLaboral"
                :items="ESTADOS_LABORALES"
                label="Condición"
                placeholder="Activos y pasivos"
                persistent-placeholder
                clearable
                hide-details
              />
            </v-col>
            <v-col cols="12">
              <v-autocomplete
                v-model="rotulos.idUbicacion"
                :items="opcionesUbicacion"
                label="Ubicación"
                placeholder="Todas las ubicaciones"
                persistent-placeholder
                clearable
                hide-details
              />
            </v-col>
            <v-col cols="12">
              <v-btn color="primary" prepend-icon="mdi-label-outline" :href="urlRotulos" target="_blank">
                Generar rótulos
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>

  <v-card>
    <v-tabs v-model="pestana" color="primary">
      <v-tab value="inventario" prepend-icon="mdi-table">Inventario ({{ filasInventario.length }})</v-tab>
      <v-tab value="pendientes" prepend-icon="mdi-clock-alert-outline">
        Préstamos pendientes ({{ pendientes.length }})
      </v-tab>
    </v-tabs>

    <v-divider />

    <v-window v-model="pestana">
      <v-window-item value="inventario">
        <v-card-text>
          <v-row density="comfortable">
            <v-col cols="12" md="6">
              <v-text-field
                v-model="filtros.buscar"
                prepend-inner-icon="mdi-magnify"
                label="Buscar en el inventario"
                clearable
                hide-details
              />
            </v-col>
            <v-col cols="12" sm="6" md="3">
              <v-select
                v-model="filtros.estadoLaboral"
                :items="ESTADOS_LABORALES"
                label="Condición"
                clearable
                hide-details
              />
            </v-col>
            <v-col cols="12" sm="6" md="3">
              <v-select
                v-model="filtros.estadoExpediente"
                :items="ESTADOS_EXPEDIENTE"
                label="Estado"
                clearable
                hide-details
              />
            </v-col>
          </v-row>
        </v-card-text>

        <v-data-table
          :headers="encabezadosInventario"
          :items="filasInventario"
          :search="filtros.buscar"
          :loading="cargando"
          item-value="id_expediente"
          loading-text="Cargando inventario..."
          no-data-text="No hay expedientes que coincidan con los filtros."
        >
          <template #item.numero_correlativo="{ item }">
            <router-link :to="{ name: 'expediente', params: { id: item.id_expediente } }">
              {{ item.numero_correlativo }}
            </router-link>
          </template>
          <template #item.estado_laboral="{ item }">
            <ChipEstado :catalogo="ESTADOS_LABORALES" :valor="item.estado_laboral" />
          </template>
          <template #item.estado_expediente="{ item }">
            <ChipEstado :catalogo="ESTADOS_EXPEDIENTE" :valor="item.estado_expediente" />
          </template>
        </v-data-table>
      </v-window-item>

      <v-window-item value="pendientes">
        <v-data-table
          :headers="encabezadosPendientes"
          :items="filasPendientes"
          :loading="cargando"
          :sort-by="[{ key: 'dias', order: 'desc' }]"
          item-value="id_prestamo"
          no-data-text="No hay préstamos pendientes de devolución."
        >
          <template #item.fecha_hora_entrega="{ item }">{{ fechaHora(item.fecha_hora_entrega) }}</template>
          <template #item.dias="{ item }">
            <v-chip :color="item.dias > 30 ? 'error' : item.dias > 7 ? 'warning' : 'default'" size="small" label>
              {{ item.dias }}
            </v-chip>
          </template>
        </v-data-table>
      </v-window-item>
    </v-window>
  </v-card>
</template>

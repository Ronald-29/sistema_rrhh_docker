<script setup>
import { computed, onMounted, ref } from 'vue'

import ChipEstado from '@/components/ChipEstado.vue'
import EncabezadoPagina from '@/components/EncabezadoPagina.vue'
import { useCarga } from '@/composables/useCarga'
import { api } from '@/services/api'
import { ACCIONES_AUDITORIA, fechaHora } from '@/utils/formato'

const { cargando, error, ejecutar } = useCarga()

const registros = ref([])
const buscar = ref('')
const accion = ref(null)
const tabla = ref(null)
const detalle = ref(null)

const tablas = [
  { title: 'Personal', value: 'personal' },
  { title: 'Ubicaciones', value: 'ubicaciones' },
  { title: 'Expedientes', value: 'expedientes' },
  { title: 'Documentos', value: 'documentos' },
  { title: 'Préstamos', value: 'prestamos' },
  { title: 'Usuarios', value: 'usuarios' }
]

const encabezados = [
  { title: 'Fecha y hora', key: 'fecha_hora' },
  { title: 'Usuario', key: 'usuario' },
  { title: 'Acción', key: 'accion' },
  { title: 'Tabla', key: 'tabla_afectada' },
  { title: 'Registro', key: 'id_registro', align: 'center' },
  { title: 'Descripción', key: 'descripcion' },
  { title: '', key: 'acciones', sortable: false, align: 'end' }
]

const filas = computed(() =>
  registros.value
    .filter((registro) =>
      (!accion.value || registro.accion === accion.value) &&
      (!tabla.value || registro.tabla_afectada === tabla.value)
    )
    .map((registro) => ({
      ...registro,
      usuario: [registro.nombres, registro.apellidos].filter(Boolean).join(' ') || registro.nombre_usuario
    }))
)

// Compara datos anteriores y nuevos campo por campo, marcando los que cambiaron.
const cambios = computed(() => {
  if (!detalle.value) {
    return []
  }

  const anteriores = detalle.value.datos_anteriores ?? {}
  const nuevos = detalle.value.datos_nuevos ?? {}
  const campos = [...new Set([...Object.keys(anteriores), ...Object.keys(nuevos)])]

  return campos.map((campo) => ({
    campo,
    anterior: formatearValor(anteriores[campo]),
    nuevo: formatearValor(nuevos[campo]),
    cambio: JSON.stringify(anteriores[campo]) !== JSON.stringify(nuevos[campo])
  }))
})

const soloCambios = ref(true)

const cambiosVisibles = computed(() =>
  soloCambios.value && detalle.value?.datos_anteriores
    ? cambios.value.filter((fila) => fila.cambio)
    : cambios.value
)

function formatearValor(valor) {
  if (valor === null || valor === undefined) return '—'
  if (typeof valor === 'boolean') return valor ? 'Sí' : 'No'
  if (typeof valor === 'object') return JSON.stringify(valor)

  return String(valor)
}

async function cargar() {
  await ejecutar(async () => {
    registros.value = (await api.get('/auditoria')).auditoria
  })
}

onMounted(cargar)
</script>

<template>
  <EncabezadoPagina subtitulo="Historial de operaciones realizadas en el sistema: quién hizo qué y cuándo." />

  <v-card>
    <v-card-text>
      <v-row density="comfortable">
        <v-col cols="12" md="6">
          <v-text-field
            v-model="buscar"
            prepend-inner-icon="mdi-magnify"
            label="Buscar por usuario o descripción"
            clearable
            hide-details
          />
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-select v-model="accion" :items="ACCIONES_AUDITORIA" label="Acción" clearable hide-details />
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-select v-model="tabla" :items="tablas" label="Tabla" clearable hide-details />
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
      item-value="id_auditoria"
      loading-text="Cargando auditoría..."
      no-data-text="No hay registros que coincidan con los filtros."
    >
      <template #item.fecha_hora="{ item }">
        <span class="text-no-wrap">{{ fechaHora(item.fecha_hora) }}</span>
      </template>
      <template #item.accion="{ item }">
        <ChipEstado :catalogo="ACCIONES_AUDITORIA" :valor="item.accion" />
      </template>
      <template #item.acciones="{ item }">
        <v-btn
          icon="mdi-eye-outline"
          variant="text"
          size="small"
          title="Ver cambios"
          aria-label="Ver cambios"
          @click="detalle = item"
        />
      </template>
    </v-data-table>
  </v-card>

  <v-dialog :model-value="detalle !== null" max-width="820" @update:model-value="detalle = null">
    <v-card v-if="detalle">
      <v-card-title class="pt-5 px-6 d-flex flex-wrap align-center ga-2">
        <span class="text-title-large">{{ detalle.descripcion }}</span>
        <ChipEstado :catalogo="ACCIONES_AUDITORIA" :valor="detalle.accion" />
      </v-card-title>
      <v-card-subtitle class="px-6">
        {{ fechaHora(detalle.fecha_hora) }} · {{ detalle.usuario }} ·
        {{ detalle.tabla_afectada }} #{{ detalle.id_registro }}
      </v-card-subtitle>

      <v-card-text class="px-6">
        <v-switch
          v-if="detalle.datos_anteriores"
          v-model="soloCambios"
          label="Mostrar solo los campos que cambiaron"
          color="primary"
          density="compact"
          hide-details
        />

        <v-table density="compact">
          <thead>
            <tr>
              <th>Campo</th>
              <th>Antes</th>
              <th>Después</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="fila in cambiosVisibles" :key="fila.campo" :class="{ 'bg-amber-lighten-5': fila.cambio && detalle.datos_anteriores }">
              <td class="font-weight-medium">{{ fila.campo }}</td>
              <td class="texto-celda">{{ fila.anterior }}</td>
              <td class="texto-celda">{{ fila.nuevo }}</td>
            </tr>
            <tr v-if="cambiosVisibles.length === 0">
              <td colspan="3" class="text-medium-emphasis">No hay diferencias entre los datos.</td>
            </tr>
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

<style scoped>
.texto-celda {
  white-space: pre-line;
  word-break: break-word;
}
</style>

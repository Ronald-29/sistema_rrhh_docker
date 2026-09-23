<script setup>
import { computed, onMounted, ref } from 'vue'

import EncabezadoPagina from '@/components/EncabezadoPagina.vue'
import UbicacionFormulario from '@/components/formularios/UbicacionFormulario.vue'
import { useCarga } from '@/composables/useCarga'
import { api, urlApi } from '@/services/api'
import { notificar } from '@/services/notificaciones'
import { sesion } from '@/services/sesion'

const { cargando, error, ejecutar } = useCarga()

const ubicaciones = ref([])
const buscar = ref('')
const filtroEstado = ref('ACTIVA')
const dialogoAbierto = ref(false)
const seleccionada = ref(null)
const cambiandoEstado = ref(null)

const opcionesEstado = [
  { title: 'Activas', value: 'ACTIVA' },
  { title: 'Inactivas', value: 'INACTIVA' }
]

const encabezados = [
  { title: 'Ambiente', key: 'ambiente' },
  { title: 'Estante', key: 'estante' },
  { title: 'Archivador', key: 'archivador' },
  { title: 'Caja', key: 'caja' },
  { title: 'Carpeta', key: 'carpeta' },
  { title: 'Descripción', key: 'descripcion' },
  { title: 'Estado', key: 'estado' },
  { title: '', key: 'acciones', sortable: false, align: 'end' }
]

const filas = computed(() =>
  ubicaciones.value.filter((ubicacion) =>
    !filtroEstado.value || ubicacion.estado === (filtroEstado.value === 'ACTIVA')
  )
)

async function cargar() {
  await ejecutar(async () => {
    ubicaciones.value = (await api.get('/ubicaciones')).ubicaciones
  })
}

function abrirFormulario(ubicacion = null) {
  seleccionada.value = ubicacion
  dialogoAbierto.value = true
}

async function cambiarEstado(ubicacion) {
  cambiandoEstado.value = ubicacion.id_ubicacion

  try {
    const respuesta = await api.put(`/ubicaciones/${ubicacion.id_ubicacion}/estado`, {
      estado: ubicacion.estado ? 'INACTIVO' : 'ACTIVO'
    })

    notificar.exito(respuesta.message)
    await cargar()
  } catch (excepcion) {
    notificar.error(excepcion)
  } finally {
    cambiandoEstado.value = null
  }
}

function urlRotulos(ubicacion) {
  return urlApi('/reportes/rotulos', { tipo: 'carpeta', id_ubicacion: ubicacion.id_ubicacion })
}

onMounted(cargar)
</script>

<template>
  <EncabezadoPagina subtitulo="Lugares físicos del archivo: ambiente, estante, archivador, caja y carpeta.">
    <template #acciones>
      <v-btn v-if="sesion.puedeEditar" color="primary" prepend-icon="mdi-plus" @click="abrirFormulario()">
        Registrar ubicación
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
            label="Buscar ubicación"
            clearable
            hide-details
          />
        </v-col>
        <v-col cols="12" md="4">
          <v-select v-model="filtroEstado" :items="opcionesEstado" label="Estado" clearable hide-details />
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
      item-value="id_ubicacion"
      loading-text="Cargando ubicaciones..."
      no-data-text="No hay ubicaciones que coincidan con los filtros."
    >
      <template #item.estado="{ item }">
        <v-chip :color="item.estado ? 'success' : 'grey'" size="small" variant="tonal" label>
          {{ item.estado ? 'Activa' : 'Inactiva' }}
        </v-chip>
      </template>

      <template #item.acciones="{ item }">
        <div class="d-flex justify-end">
          <v-btn
            icon="mdi-label-outline"
            variant="text"
            size="small"
            :href="urlRotulos(item)"
            target="_blank"
            aria-label="Rótulos de las carpetas de esta ubicación"
            title="Rótulos de las carpetas de esta ubicación"
          />
          <template v-if="sesion.puedeEditar">
            <v-btn
              icon="mdi-pencil-outline"
              variant="text"
              size="small"
              aria-label="Editar"
              title="Editar"
              @click="abrirFormulario(item)"
            />
            <v-btn
              :icon="item.estado ? 'mdi-archive-off-outline' : 'mdi-archive-check-outline'"
              variant="text"
              size="small"
              :loading="cambiandoEstado === item.id_ubicacion"
              :aria-label="item.estado ? 'Desactivar' : 'Activar'"
              :title="item.estado ? 'Desactivar' : 'Activar'"
              @click="cambiarEstado(item)"
            />
          </template>
        </div>
      </template>
    </v-data-table>
  </v-card>

  <UbicacionFormulario v-model="dialogoAbierto" :ubicacion="seleccionada" @guardado="cargar" />
</template>

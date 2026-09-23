<script setup>
import { computed, onMounted, ref } from 'vue'

import ChipEstado from '@/components/ChipEstado.vue'
import EncabezadoPagina from '@/components/EncabezadoPagina.vue'
import PersonalFormulario from '@/components/formularios/PersonalFormulario.vue'
import { useCarga } from '@/composables/useCarga'
import { api } from '@/services/api'
import { sesion } from '@/services/sesion'
import { ESTADOS_LABORALES, nombreCompleto, periodoLaboral } from '@/utils/formato'

const { cargando, error, ejecutar } = useCarga()

const personal = ref([])
const buscar = ref('')
const estadoLaboral = ref(null)
const dialogoAbierto = ref(false)
const seleccionado = ref(null)

const encabezados = computed(() => [
  { title: 'Nombre completo', key: 'nombre_completo' },
  { title: 'N° de documento', key: 'numero_documento' },
  { title: 'Cargo', key: 'cargo' },
  { title: 'Condición', key: 'estado_laboral' },
  { title: 'Periodo laboral', key: 'periodo', sortable: false },
  ...(sesion.puedeEditar ? [{ title: '', key: 'acciones', sortable: false, align: 'end' }] : [])
])

const filas = computed(() =>
  personal.value
    .filter((persona) => !estadoLaboral.value || persona.estado_laboral === estadoLaboral.value)
    .map((persona) => ({
      ...persona,
      nombre_completo: nombreCompleto(persona),
      periodo: periodoLaboral(persona)
    }))
)

async function cargar() {
  await ejecutar(async () => {
    personal.value = (await api.get('/personal')).personal
  })
}

function abrirFormulario(persona = null) {
  seleccionado.value = persona
  dialogoAbierto.value = true
}

onMounted(cargar)
</script>

<template>
  <EncabezadoPagina subtitulo="Servidores y ex servidores públicos cuyos expedientes están en el archivo.">
    <template #acciones>
      <v-btn v-if="sesion.puedeEditar" color="primary" prepend-icon="mdi-plus" @click="abrirFormulario()">
        Registrar personal
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
            label="Buscar por nombre, documento o cargo"
            clearable
            hide-details
          />
        </v-col>
        <v-col cols="12" md="4">
          <v-select v-model="estadoLaboral" :items="ESTADOS_LABORALES" label="Condición" clearable hide-details />
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
      :sort-by="[{ key: 'nombre_completo', order: 'asc' }]"
      item-value="id_personal"
      loading-text="Cargando personal..."
      no-data-text="No hay personal que coincida con los filtros."
    >
      <template #item.estado_laboral="{ item }">
        <ChipEstado :catalogo="ESTADOS_LABORALES" :valor="item.estado_laboral" />
      </template>

      <template #item.acciones="{ item }">
        <v-btn
          icon="mdi-pencil-outline"
          variant="text"
          size="small"
          aria-label="Editar"
          @click="abrirFormulario(item)"
        />
      </template>
    </v-data-table>
  </v-card>

  <PersonalFormulario v-model="dialogoAbierto" :personal="seleccionado" @guardado="cargar" />
</template>

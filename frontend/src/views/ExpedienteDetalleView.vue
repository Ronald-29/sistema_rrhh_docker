<script setup>
import { computed, ref, watch } from 'vue'

import ChipEstado from '@/components/ChipEstado.vue'
import EncabezadoPagina from '@/components/EncabezadoPagina.vue'
import DevolucionFormulario from '@/components/formularios/DevolucionFormulario.vue'
import DocumentoFormulario from '@/components/formularios/DocumentoFormulario.vue'
import ExpedienteFormulario from '@/components/formularios/ExpedienteFormulario.vue'
import PrestamoFormulario from '@/components/formularios/PrestamoFormulario.vue'
import RevisionFormulario from '@/components/formularios/RevisionFormulario.vue'
import { useCarga } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'
import { sesion } from '@/services/sesion'
import {
  ESTADOS_DOCUMENTO,
  ESTADOS_EXPEDIENTE,
  ESTADOS_LABORALES,
  ESTADOS_PRESTAMO,
  fecha,
  fechaHora,
  nombreCompleto,
  periodoLaboral,
  ubicacionTexto
} from '@/utils/formato'

const props = defineProps({
  id: { type: Number, required: true }
})

const { cargando, error, ejecutar } = useCarga()

const expediente = ref(null)
const personal = ref(null)
const documentos = ref([])
const prestamos = ref([])

const dialogos = ref({
  expediente: false,
  revision: false,
  anularRevision: false,
  documento: false,
  prestamo: false,
  devolucion: false
})

const documentoSeleccionado = ref(null)
const documentoAPrestar = ref(null)
const prestamoSeleccionado = ref(null)
const anulando = ref(false)

const prestamoActivoExpediente = computed(() =>
  prestamos.value.find(
    (prestamo) => prestamo.estado === 'PRESTADO' && prestamo.id_expediente === props.id
  ) ?? null
)

const hayDocumentosPrestados = computed(() =>
  documentos.value.some((documento) => documento.estado_documento === 'PRESTADO')
)

const encabezadosDocumentos = computed(() => [
  { title: 'Documento', key: 'nombre_documento' },
  { title: 'Tipo', key: 'tipo_documento' },
  { title: 'N°', key: 'numero_documento' },
  { title: 'Fecha', key: 'fecha_documento' },
  { title: 'Estado', key: 'estado_documento' },
  { title: 'Observaciones', key: 'observaciones', sortable: false },
  ...(sesion.puedeEditar ? [{ title: '', key: 'acciones', sortable: false, align: 'end' }] : [])
])

const encabezadosPrestamos = computed(() => [
  { title: 'Préstamo de', key: 'objeto', sortable: false },
  { title: 'Entrega', key: 'entrega', sortable: false },
  { title: 'Devolución', key: 'devolucion', sortable: false },
  { title: 'Estado', key: 'estado' },
  ...(sesion.puedeEditar ? [{ title: '', key: 'acciones', sortable: false, align: 'end' }] : [])
])

async function cargar() {
  await ejecutar(async () => {
    const [respuestaExpediente, respuestaDocumentos, respuestaPrestamos] = await Promise.all([
      api.get(`/expedientes/${props.id}`),
      api.get(`/expedientes/${props.id}/documentos`),
      api.get('/prestamos')
    ])

    expediente.value = respuestaExpediente.expediente
    documentos.value = respuestaDocumentos.documentos

    const idsDocumentos = new Set(documentos.value.map((documento) => documento.id_documento))

    prestamos.value = respuestaPrestamos.prestamos.filter(
      (prestamo) => prestamo.id_expediente === props.id || idsDocumentos.has(prestamo.id_documento)
    )

    personal.value = (await api.get(`/personal/${expediente.value.id_personal}`)).personal
  })
}

function editarDocumento(documento = null) {
  documentoSeleccionado.value = documento
  dialogos.value.documento = true
}

function prestar(documento = null) {
  documentoAPrestar.value = documento
  dialogos.value.prestamo = true
}

function devolver(prestamo) {
  prestamoSeleccionado.value = prestamo
  dialogos.value.devolucion = true
}

function nombreDocumento(idDocumento) {
  return documentos.value.find((documento) => documento.id_documento === idDocumento)?.nombre_documento
}

async function anularRevision() {
  anulando.value = true

  try {
    const respuesta = await api.delete(`/expedientes/${props.id}/revision`)
    notificar.exito(respuesta.message)
    dialogos.value.anularRevision = false
    await cargar()
  } catch (excepcion) {
    notificar.error(excepcion)
  } finally {
    anulando.value = false
  }
}

watch(() => props.id, cargar, { immediate: true })
</script>

<template>
  <v-btn
    variant="text"
    prepend-icon="mdi-arrow-left"
    class="mb-2 px-1"
    :to="{ name: 'expedientes' }"
  >
    Volver a expedientes
  </v-btn>

  <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
    {{ error }}
    <template #append>
      <v-btn variant="text" @click="cargar">Reintentar</v-btn>
    </template>
  </v-alert>

  <v-skeleton-loader v-if="cargando && !expediente" type="heading, card, table" />

  <template v-if="expediente">
    <EncabezadoPagina :titulo="`Expediente N° ${expediente.numero_correlativo}`">
      <template #detalle>
        <div class="d-flex flex-wrap align-center ga-2 mt-1">
          <span class="text-title-medium">{{ nombreCompleto(expediente) }}</span>
          <ChipEstado :catalogo="ESTADOS_LABORALES" :valor="expediente.estado_laboral" />
          <ChipEstado :catalogo="ESTADOS_EXPEDIENTE" :valor="expediente.estado_expediente" />
        </div>
      </template>

      <template v-if="sesion.puedeEditar" #acciones>
        <v-btn variant="tonal" prepend-icon="mdi-pencil-outline" @click="dialogos.expediente = true">
          Editar
        </v-btn>
        <v-btn
          v-if="!expediente.revisado"
          color="secondary"
          prepend-icon="mdi-clipboard-check-outline"
          :disabled="!!prestamoActivoExpediente"
          @click="dialogos.revision = true"
        >
          Registrar revisión
        </v-btn>
        <v-btn
          color="primary"
          prepend-icon="mdi-export"
          :disabled="!!prestamoActivoExpediente || hayDocumentosPrestados"
          @click="prestar()"
        >
          Prestar expediente
        </v-btn>
      </template>
    </EncabezadoPagina>

    <v-alert v-if="prestamoActivoExpediente" type="warning" variant="tonal" class="mb-4">
      El expediente está prestado a <b>{{ prestamoActivoExpediente.recibido_por }}</b> desde el
      {{ fechaHora(prestamoActivoExpediente.fecha_hora_entrega) }}.
      <template v-if="sesion.puedeEditar" #append>
        <v-btn variant="text" @click="devolver(prestamoActivoExpediente)">Registrar devolución</v-btn>
      </template>
    </v-alert>

    <v-row>
      <v-col cols="12" md="6">
        <v-card height="100%">
          <v-card-item title="Servidor público" prepend-icon="mdi-account-outline" />
          <v-card-text>
            <v-table density="compact">
              <tbody>
                <tr>
                  <th class="w-33">Nombre completo</th>
                  <td>{{ nombreCompleto(expediente) }}</td>
                </tr>
                <tr>
                  <th>N° de documento</th>
                  <td>{{ expediente.numero_documento || '—' }}</td>
                </tr>
                <tr>
                  <th>Cargo</th>
                  <td>{{ expediente.cargo }}</td>
                </tr>
                <tr>
                  <th>Condición</th>
                  <td><ChipEstado :catalogo="ESTADOS_LABORALES" :valor="expediente.estado_laboral" /></td>
                </tr>
                <tr>
                  <th>Gestión o periodo</th>
                  <td>{{ periodoLaboral(personal) || '—' }}</td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card height="100%">
          <v-card-item title="Ubicación y estado" prepend-icon="mdi-archive-outline" />
          <v-card-text>
            <v-table density="compact">
              <tbody>
                <tr>
                  <th class="w-33">Ubicación física</th>
                  <td>{{ expediente.id_ubicacion ? ubicacionTexto(expediente) : 'Sin ubicación asignada' }}</td>
                </tr>
                <tr>
                  <th>Estado</th>
                  <td><ChipEstado :catalogo="ESTADOS_EXPEDIENTE" :valor="expediente.estado_expediente" /></td>
                </tr>
                <tr>
                  <th>Observaciones</th>
                  <td class="texto-multilinea">{{ expediente.observaciones || '—' }}</td>
                </tr>
                <tr>
                  <th>Registrado</th>
                  <td>{{ fechaHora(expediente.fecha_registro) }}</td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12">
        <v-card>
          <v-card-item title="Revisión física" prepend-icon="mdi-clipboard-check-outline" />
          <v-card-text>
            <div v-if="expediente.revisado" class="d-flex flex-wrap align-center ga-4">
              <v-icon icon="mdi-check-circle" color="success" size="32" />
              <div class="flex-grow-1">
                <div class="text-title-medium">Revisado por {{ expediente.revisado_por }}</div>
                <div class="text-body-medium text-medium-emphasis">
                  {{ fechaHora(expediente.fecha_revision) }} · registrado por el usuario
                  {{ expediente.usuario_revision }}
                </div>
              </div>
              <v-btn
                v-if="sesion.esAdministrador"
                variant="text"
                color="error"
                prepend-icon="mdi-undo"
                @click="dialogos.anularRevision = true"
              >
                Anular revisión
              </v-btn>
            </div>
            <div v-else class="d-flex align-center ga-4">
              <v-icon icon="mdi-clock-outline" color="grey" size="32" />
              <div>
                <div class="text-title-medium">Pendiente de revisión</div>
                <div class="text-body-medium text-medium-emphasis">
                  {{ prestamoActivoExpediente
                    ? 'Se podrá revisar cuando el expediente sea devuelto.'
                    : 'Aún no se registró la revisión física de este expediente.' }}
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12">
        <v-card>
          <v-card-item title="Documentos" prepend-icon="mdi-file-document-multiple-outline">
            <template v-if="sesion.puedeEditar" #append>
              <v-btn variant="tonal" prepend-icon="mdi-plus" @click="editarDocumento()">Agregar documento</v-btn>
            </template>
          </v-card-item>

          <v-data-table
            :headers="encabezadosDocumentos"
            :items="documentos"
            item-value="id_documento"
            no-data-text="El expediente no tiene documentos registrados."
            :items-per-page="-1"
            hide-default-footer
          >
            <template #item.fecha_documento="{ item }">{{ fecha(item.fecha_documento) }}</template>

            <template #item.estado_documento="{ item }">
              <ChipEstado :catalogo="ESTADOS_DOCUMENTO" :valor="item.estado_documento" />
            </template>

            <template #item.acciones="{ item }">
              <div class="d-flex justify-end">
                <v-btn
                  icon="mdi-export"
                  variant="text"
                  size="small"
                  title="Prestar documento"
                  aria-label="Prestar documento"
                  :disabled="item.estado_documento !== 'DISPONIBLE' || !!prestamoActivoExpediente"
                  @click="prestar(item)"
                />
                <v-btn
                  icon="mdi-pencil-outline"
                  variant="text"
                  size="small"
                  :title="item.estado_documento === 'PRESTADO' ? 'No se puede editar mientras está prestado' : 'Editar'"
                  aria-label="Editar documento"
                  :disabled="item.estado_documento === 'PRESTADO'"
                  @click="editarDocumento(item)"
                />
              </div>
            </template>
          </v-data-table>
        </v-card>
      </v-col>

      <v-col cols="12">
        <v-card>
          <v-card-item title="Historial de préstamos" prepend-icon="mdi-swap-horizontal" />

          <v-data-table
            :headers="encabezadosPrestamos"
            :items="prestamos"
            item-value="id_prestamo"
            no-data-text="Este expediente no tiene préstamos registrados."
          >
            <template #item.objeto="{ item }">
              <span v-if="item.id_documento">
                <v-icon icon="mdi-file-document-outline" size="small" />
                {{ nombreDocumento(item.id_documento) ?? item.nombre_documento }}
              </span>
              <span v-else><v-icon icon="mdi-folder-outline" size="small" /> Expediente completo</span>
            </template>

            <template #item.entrega="{ item }">
              <div>{{ item.entregado_por }} → {{ item.recibido_por }}</div>
              <div class="text-body-small text-medium-emphasis">{{ fechaHora(item.fecha_hora_entrega) }}</div>
            </template>

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
              <v-btn
                v-if="item.estado === 'PRESTADO'"
                variant="text"
                color="success"
                size="small"
                @click="devolver(item)"
              >
                Devolver
              </v-btn>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>

    <ExpedienteFormulario v-model="dialogos.expediente" :expediente="expediente" @guardado="cargar" />
    <RevisionFormulario v-model="dialogos.revision" :expediente="expediente" @guardado="cargar" />
    <DocumentoFormulario
      v-model="dialogos.documento"
      :id-expediente="id"
      :documento="documentoSeleccionado"
      @guardado="cargar"
    />
    <PrestamoFormulario
      v-model="dialogos.prestamo"
      :expediente="documentoAPrestar ? null : expediente"
      :documento="documentoAPrestar"
      @guardado="cargar"
    />
    <DevolucionFormulario
      v-if="prestamoSeleccionado"
      v-model="dialogos.devolucion"
      :prestamo="prestamoSeleccionado"
      @guardado="cargar"
    />

    <v-dialog v-model="dialogos.anularRevision" max-width="480">
      <v-card>
        <v-card-title class="pt-5 px-6 text-title-large">¿Anular la revisión física?</v-card-title>
        <v-card-text class="px-6">
          El expediente N° {{ expediente.numero_correlativo }} volverá a figurar como pendiente de
          revisión. El cambio queda registrado en la auditoría.
        </v-card-text>
        <v-card-actions class="px-6 pb-5">
          <v-spacer />
          <v-btn variant="text" :disabled="anulando" @click="dialogos.anularRevision = false">Cancelar</v-btn>
          <v-btn color="error" variant="flat" :loading="anulando" @click="anularRevision">Anular revisión</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </template>
</template>

<style scoped>
.texto-multilinea {
  white-space: pre-line;
}
</style>

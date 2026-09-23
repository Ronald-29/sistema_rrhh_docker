<script setup>
import { computed, onMounted, ref } from 'vue'

import EncabezadoPagina from '@/components/EncabezadoPagina.vue'
import PasswordFormulario from '@/components/formularios/PasswordFormulario.vue'
import UsuarioFormulario from '@/components/formularios/UsuarioFormulario.vue'
import { useCarga } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'
import { sesion } from '@/services/sesion'
import { COLORES_ROL, ESTADOS_USUARIO, fechaHora } from '@/utils/formato'

const { cargando, error, ejecutar } = useCarga()

const usuarios = ref([])
const buscar = ref('')
const filtroEstado = ref(null)
const dialogoUsuario = ref(false)
const dialogoPassword = ref(false)
const seleccionado = ref(null)
const cambiandoEstado = ref(null)

const encabezados = [
  { title: 'Usuario', key: 'nombre_usuario' },
  { title: 'Nombre completo', key: 'nombre_completo' },
  { title: 'Rol', key: 'rol' },
  { title: 'Estado', key: 'estado' },
  { title: 'Último acceso', key: 'ultimo_acceso' },
  { title: '', key: 'acciones', sortable: false, align: 'end' }
]

const filas = computed(() =>
  usuarios.value
    .filter((usuario) =>
      !filtroEstado.value || usuario.estado === (filtroEstado.value === 'ACTIVO')
    )
    .map((usuario) => ({
      ...usuario,
      nombre_completo: `${usuario.nombres} ${usuario.apellidos}`.trim(),
      es_propio: usuario.id_usuario === sesion.usuario?.id_usuario
    }))
)

async function cargar() {
  await ejecutar(async () => {
    usuarios.value = (await api.get('/usuarios')).usuarios
  })
}

function abrirFormulario(usuario = null) {
  seleccionado.value = usuario
  dialogoUsuario.value = true
}

function abrirPassword(usuario) {
  seleccionado.value = usuario
  dialogoPassword.value = true
}

async function cambiarEstado(usuario) {
  cambiandoEstado.value = usuario.id_usuario

  try {
    const respuesta = await api.put(`/usuarios/${usuario.id_usuario}/estado`, {
      estado: usuario.estado ? 'INACTIVO' : 'ACTIVO'
    })

    notificar.exito(respuesta.message)
    await cargar()
  } catch (excepcion) {
    notificar.error(excepcion)
  } finally {
    cambiandoEstado.value = null
  }
}

onMounted(cargar)
</script>

<template>
  <EncabezadoPagina subtitulo="Usuarios que pueden ingresar al sistema y el rol con el que trabajan.">
    <template #acciones>
      <v-btn color="primary" prepend-icon="mdi-account-plus-outline" @click="abrirFormulario()">
        Registrar usuario
      </v-btn>
    </template>
  </EncabezadoPagina>

  <v-alert type="info" variant="tonal" density="compact" class="mb-4">
    Al desactivar un usuario, su sesión se cierra en la siguiente acción que realice.
  </v-alert>

  <v-card>
    <v-card-text>
      <v-row density="comfortable">
        <v-col cols="12" md="8">
          <v-text-field
            v-model="buscar"
            prepend-inner-icon="mdi-magnify"
            label="Buscar por usuario, nombre o rol"
            clearable
            hide-details
          />
        </v-col>
        <v-col cols="12" md="4">
          <v-select
            v-model="filtroEstado"
            :items="ESTADOS_USUARIO"
            label="Estado"
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
      :search="buscar"
      :loading="cargando"
      item-value="id_usuario"
      loading-text="Cargando usuarios..."
      no-data-text="No hay usuarios que coincidan con los filtros."
    >
      <template #item.nombre_usuario="{ item }">
        <span class="font-weight-medium">{{ item.nombre_usuario }}</span>
        <v-chip v-if="item.es_propio" size="x-small" class="ms-2" label>Usted</v-chip>
      </template>

      <template #item.rol="{ item }">
        <v-chip :color="COLORES_ROL[item.rol] ?? 'grey'" size="small" variant="tonal" label>
          {{ item.rol }}
        </v-chip>
      </template>

      <template #item.estado="{ item }">
        <v-chip :color="item.estado ? 'success' : 'grey'" size="small" variant="tonal" label>
          {{ item.estado ? 'Activo' : 'Inactivo' }}
        </v-chip>
      </template>

      <template #item.ultimo_acceso="{ item }">
        <span v-if="item.ultimo_acceso">{{ fechaHora(item.ultimo_acceso) }}</span>
        <span v-else class="text-medium-emphasis">Nunca ingresó</span>
      </template>

      <template #item.acciones="{ item }">
        <div class="d-flex justify-end">
          <v-btn
            icon="mdi-pencil-outline"
            variant="text"
            size="small"
            title="Editar"
            aria-label="Editar usuario"
            @click="abrirFormulario(item)"
          />
          <v-btn
            icon="mdi-key-outline"
            variant="text"
            size="small"
            title="Asignar contraseña nueva"
            aria-label="Asignar contraseña nueva"
            @click="abrirPassword(item)"
          />
          <v-btn
            :icon="item.estado ? 'mdi-account-off-outline' : 'mdi-account-check-outline'"
            variant="text"
            size="small"
            :loading="cambiandoEstado === item.id_usuario"
            :disabled="item.es_propio"
            :title="item.es_propio
              ? 'No puede desactivar su propio usuario'
              : (item.estado ? 'Desactivar' : 'Activar')"
            :aria-label="item.estado ? 'Desactivar usuario' : 'Activar usuario'"
            @click="cambiarEstado(item)"
          />
        </div>
      </template>
    </v-data-table>
  </v-card>

  <UsuarioFormulario v-model="dialogoUsuario" :usuario="seleccionado" @guardado="cargar" />

  <PasswordFormulario
    v-if="seleccionado"
    v-model="dialogoPassword"
    :usuario="seleccionado"
    @guardado="cargar"
  />
</template>

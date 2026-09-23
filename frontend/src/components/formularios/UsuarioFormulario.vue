<script setup>
import { computed, ref, watch } from 'vue'

import DialogoFormulario from '@/components/DialogoFormulario.vue'
import { useCarga, useGuardado } from '@/composables/useCarga'
import { api } from '@/services/api'
import { notificar } from '@/services/notificaciones'
import { sesion } from '@/services/sesion'
import { reglas } from '@/utils/formato'

const abierto = defineModel({ type: Boolean, default: false })

const props = defineProps({
  usuario: { type: Object, default: null }
})

const emit = defineEmits(['guardado'])

const { guardando, error, guardar } = useGuardado()
const { cargando: cargandoRoles, error: errorRoles, ejecutar } = useCarga()

const formulario = ref(vacio())
const roles = ref([])
const mostrarPassword = ref(false)

const editando = computed(() => props.usuario !== null)

const esPropioUsuario = computed(
  () => editando.value && props.usuario.id_usuario === sesion.usuario?.id_usuario
)

const opcionesRol = computed(() =>
  roles.value.map((rol) => ({
    value: rol.id_rol,
    title: rol.nombre,
    subtitle: rol.descripcion ?? ''
  }))
)

function vacio() {
  return {
    nombres: '',
    apellidos: '',
    nombre_usuario: '',
    id_rol: null,
    password: ''
  }
}

const reglaNombreUsuario = (valor) =>
  /^[A-Za-z0-9._-]{3,50}$/.test(valor ?? '') ||
  'Entre 3 y 50 caracteres: letras, números, punto, guion o guion bajo.'

const reglaPassword = (valor) =>
  (valor ?? '').length >= 8 || 'La contraseña debe tener al menos 8 caracteres.'

watch(abierto, async (valor) => {
  if (!valor) {
    return
  }

  error.value = ''
  mostrarPassword.value = false

  formulario.value = editando.value
    ? {
        nombres: props.usuario.nombres,
        apellidos: props.usuario.apellidos,
        nombre_usuario: props.usuario.nombre_usuario,
        id_rol: props.usuario.id_rol,
        password: ''
      }
    : vacio()

  await ejecutar(async () => {
    roles.value = (await api.get('/roles')).roles
  })
})

async function enviar() {
  const datos = {
    nombres: formulario.value.nombres.trim(),
    apellidos: formulario.value.apellidos.trim(),
    nombre_usuario: formulario.value.nombre_usuario.trim(),
    id_rol: formulario.value.id_rol
  }
  let respuesta

  const exito = await guardar(async () => {
    respuesta = editando.value
      ? await api.put(`/usuarios/${props.usuario.id_usuario}`, datos)
      : await api.post('/usuarios', { ...datos, password: formulario.value.password })
  })

  if (exito) {
    notificar.exito(respuesta.message)
    abierto.value = false
    emit('guardado', respuesta)
  }
}
</script>

<template>
  <DialogoFormulario
    v-model="abierto"
    :titulo="editando ? 'Editar usuario' : 'Registrar usuario'"
    :guardando="guardando"
    :error="error || errorRoles"
    @guardar="enviar"
  >
    <v-row density="comfortable">
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.nombres"
          label="Nombres *"
          :rules="[reglas.obligatorio, reglas.maximo(100)]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.apellidos"
          label="Apellidos *"
          :rules="[reglas.obligatorio, reglas.maximo(100)]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="formulario.nombre_usuario"
          label="Nombre de usuario *"
          hint="Con este nombre inicia sesión."
          :rules="[reglaNombreUsuario]"
        />
      </v-col>
      <v-col cols="12" sm="6">
        <v-select
          v-model="formulario.id_rol"
          :items="opcionesRol"
          :loading="cargandoRoles"
          :disabled="esPropioUsuario"
          label="Rol *"
          :hint="esPropioUsuario ? 'No puede cambiar su propio rol.' : ''"
          persistent-hint
          :rules="[reglas.obligatorio]"
        >
          <template #item="{ props: propiedades, item }">
            <v-list-item v-bind="propiedades" :subtitle="item.subtitle" />
          </template>
        </v-select>
      </v-col>
      <v-col v-if="!editando" cols="12">
        <v-text-field
          v-model="formulario.password"
          label="Contraseña *"
          :type="mostrarPassword ? 'text' : 'password'"
          :append-inner-icon="mostrarPassword ? 'mdi-eye-off' : 'mdi-eye'"
          autocomplete="new-password"
          hint="Mínimo 8 caracteres. El usuario podrá cambiarla luego."
          :rules="[reglaPassword]"
          @click:append-inner="mostrarPassword = !mostrarPassword"
        />
      </v-col>
    </v-row>
  </DialogoFormulario>
</template>

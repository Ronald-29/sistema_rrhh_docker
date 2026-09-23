import { computed, reactive } from 'vue'
import { api } from './api'

export const ROLES = {
  ADMINISTRADOR: 'Administrador',
  RECURSOS_HUMANOS: 'Recursos Humanos',
  CONSULTA: 'Consulta'
}

const estado = reactive({
  usuario: null,
  verificada: false
})

// reactive() desenvuelve los computed: se usa sesion.puedeEditar, sin .value.
export const sesion = reactive({
  usuario: computed(() => estado.usuario),
  verificada: computed(() => estado.verificada),
  autenticado: computed(() => estado.usuario !== null),

  // Los mismos permisos que aplica AuthMiddleware::verificarRol en el backend.
  puedeEditar: computed(() =>
    [ROLES.ADMINISTRADOR, ROLES.RECURSOS_HUMANOS].includes(estado.usuario?.rol)
  ),
  esAdministrador: computed(() => estado.usuario?.rol === ROLES.ADMINISTRADOR),

  nombreVisible: computed(() => {
    const usuario = estado.usuario

    if (!usuario) {
      return ''
    }

    const nombre = [usuario.nombres, usuario.apellidos].filter(Boolean).join(' ').trim()

    return nombre || usuario.nombre_usuario
  })
})

function guardarUsuario(usuario) {
  estado.usuario = usuario
    ? {
        id_usuario: usuario.id_usuario,
        nombre_usuario: usuario.nombre_usuario,
        nombres: usuario.nombres,
        apellidos: usuario.apellidos,
        rol: usuario.rol
      }
    : null
}

export async function verificarSesion() {
  if (estado.verificada) {
    return
  }

  try {
    const respuesta = await api.get('/me')
    guardarUsuario(respuesta.usuario)
  } catch {
    guardarUsuario(null)
  } finally {
    estado.verificada = true
  }
}

export async function iniciarSesion(nombreUsuario, password) {
  const respuesta = await api.post('/login', {
    nombre_usuario: nombreUsuario,
    password
  })

  guardarUsuario(respuesta.usuario)
  estado.verificada = true
}

export async function cerrarSesion() {
  try {
    await api.post('/logout')
  } finally {
    olvidarSesion()
  }
}

export function olvidarSesion() {
  guardarUsuario(null)
  estado.verificada = true
}

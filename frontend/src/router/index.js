import { createRouter, createWebHistory } from 'vue-router'

import { sesion, verificarSesion } from '@/services/sesion'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { publica: true, titulo: 'Iniciar sesión' }
  },
  {
    path: '/',
    component: () => import('@/layouts/LayoutPrincipal.vue'),
    children: [
      {
        path: '',
        name: 'inicio',
        component: () => import('@/views/InicioView.vue'),
        meta: { titulo: 'Inicio' }
      },
      {
        path: 'expedientes',
        name: 'expedientes',
        component: () => import('@/views/ExpedientesView.vue'),
        meta: { titulo: 'Expedientes' }
      },
      {
        path: 'expedientes/:id(\\d+)',
        name: 'expediente',
        component: () => import('@/views/ExpedienteDetalleView.vue'),
        props: (ruta) => ({ id: Number(ruta.params.id) }),
        meta: { titulo: 'Expediente' }
      },
      {
        path: 'personal',
        name: 'personal',
        component: () => import('@/views/PersonalView.vue'),
        meta: { titulo: 'Personal' }
      },
      {
        path: 'ubicaciones',
        name: 'ubicaciones',
        component: () => import('@/views/UbicacionesView.vue'),
        meta: { titulo: 'Ubicaciones' }
      },
      {
        path: 'prestamos',
        name: 'prestamos',
        component: () => import('@/views/PrestamosView.vue'),
        meta: { titulo: 'Préstamos' }
      },
      {
        path: 'reportes',
        name: 'reportes',
        component: () => import('@/views/ReportesView.vue'),
        meta: { titulo: 'Reportes' }
      },
      {
        path: 'usuarios',
        name: 'usuarios',
        component: () => import('@/views/UsuariosView.vue'),
        meta: { titulo: 'Usuarios', soloAdministrador: true }
      },
      {
        path: 'auditoria',
        name: 'auditoria',
        component: () => import('@/views/AuditoriaView.vue'),
        meta: { titulo: 'Auditoría', soloAdministrador: true }
      },
      {
        path: ':rutaDesconocida(.*)*',
        name: 'no-encontrado',
        component: () => import('@/views/NoEncontradoView.vue'),
        meta: { titulo: 'Página no encontrada' }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

router.beforeEach(async (destino) => {
  await verificarSesion()

  if (destino.meta.publica) {
    return sesion.autenticado && destino.name === 'login'
      ? { name: 'inicio' }
      : true
  }

  if (!sesion.autenticado) {
    return {
      name: 'login',
      query: destino.fullPath !== '/' ? { redirigir: destino.fullPath } : {}
    }
  }

  if (destino.meta.soloAdministrador && !sesion.esAdministrador) {
    return { name: 'inicio' }
  }

  return true
})

router.afterEach((destino) => {
  document.title = destino.meta.titulo
    ? `${destino.meta.titulo} - Archivo RRHH EMAPA`
    : 'Archivo RRHH EMAPA'
})

export default router

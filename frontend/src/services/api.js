// Cliente del backend PHP.
// El frontend y la API se sirven desde el mismo origen (en desarrollo, Vite
// redirige /api al backend), así la cookie de sesión PHP viaja sola.

const BASE_API = '/api'

export class ErrorApi extends Error {
  constructor(mensaje, estado, datos = null) {
    super(mensaje)
    this.name = 'ErrorApi'
    this.estado = estado
    this.datos = datos
  }
}

let manejadorSesionVencida = null

// Se ejecuta cuando el backend responde 401 (sesión vencida o cerrada).
export function alVencerSesion(manejador) {
  manejadorSesionVencida = manejador
}

export function urlApi(ruta, parametros = {}) {
  const consulta = new URLSearchParams()

  for (const [clave, valor] of Object.entries(parametros)) {
    if (valor !== null && valor !== undefined && valor !== '') {
      consulta.append(clave, String(valor))
    }
  }

  const texto = consulta.toString()

  return `${BASE_API}${ruta}${texto ? `?${texto}` : ''}`
}

async function solicitar(metodo, ruta, { parametros, cuerpo } = {}) {
  const opciones = {
    method: metodo,
    credentials: 'same-origin',
    headers: { Accept: 'application/json' }
  }

  if (cuerpo !== undefined) {
    opciones.headers['Content-Type'] = 'application/json'
    opciones.body = JSON.stringify(cuerpo)
  }

  let respuesta

  try {
    respuesta = await fetch(urlApi(ruta, parametros), opciones)
  } catch {
    throw new ErrorApi('No se pudo conectar con el servidor. Verifique que el backend esté en ejecución.', 0)
  }

  let datos = null

  try {
    datos = await respuesta.json()
  } catch {
    datos = null
  }

  if (!respuesta.ok || datos?.success === false) {
    if (respuesta.status === 401 && ruta !== '/login' && manejadorSesionVencida) {
      manejadorSesionVencida(datos ?? {})
    }

    throw new ErrorApi(
      datos?.message ?? `El servidor respondió con un error (${respuesta.status}).`,
      respuesta.status,
      datos
    )
  }

  return datos
}

export const api = {
  get: (ruta, parametros) => solicitar('GET', ruta, { parametros }),
  post: (ruta, cuerpo = {}) => solicitar('POST', ruta, { cuerpo }),
  put: (ruta, cuerpo = {}) => solicitar('PUT', ruta, { cuerpo }),
  delete: (ruta) => solicitar('DELETE', ruta)
}

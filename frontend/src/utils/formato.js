// Catálogos y formatos compartidos. Los valores coinciden con los CHECK de la base de datos.

export const ESTADOS_LABORALES = [
  { title: 'Activo', value: 'ACTIVO', color: 'success' },
  { title: 'Pasivo', value: 'PASIVO', color: 'blue-grey' }
]

export const ESTADOS_EXPEDIENTE = [
  { title: 'Completo', value: 'COMPLETO', color: 'success' },
  { title: 'Incompleto', value: 'INCOMPLETO', color: 'warning' },
  { title: 'Deteriorado', value: 'DETERIORADO', color: 'error' },
  { title: 'Observado', value: 'OBSERVADO', color: 'info' }
]

// PRESTADO lo asigna el módulo de préstamos; no se elige en el formulario.
export const ESTADOS_DOCUMENTO = [
  { title: 'Disponible', value: 'DISPONIBLE', color: 'success' },
  { title: 'Prestado', value: 'PRESTADO', color: 'warning' },
  { title: 'Deteriorado', value: 'DETERIORADO', color: 'error' },
  { title: 'Extraviado', value: 'EXTRAVIADO', color: 'grey' }
]

export const ESTADOS_DOCUMENTO_EDITABLES = ESTADOS_DOCUMENTO.filter(
  (estado) => estado.value !== 'PRESTADO'
)

export const ESTADOS_PRESTAMO = [
  { title: 'Prestado', value: 'PRESTADO', color: 'warning' },
  { title: 'Devuelto', value: 'DEVUELTO', color: 'success' }
]

// Los roles se leen de /api/roles; aquí solo se define su color.
export const COLORES_ROL = {
  Administrador: 'deep-purple',
  'Recursos Humanos': 'primary',
  Consulta: 'blue-grey'
}

export const ESTADOS_USUARIO = [
  { title: 'Activo', value: 'ACTIVO', color: 'success' },
  { title: 'Inactivo', value: 'INACTIVO', color: 'grey' }
]

export const ACCIONES_AUDITORIA = [
  { title: 'Crear', value: 'CREAR', color: 'success' },
  { title: 'Modificar', value: 'MODIFICAR', color: 'info' },
  { title: 'Eliminar', value: 'ELIMINAR', color: 'error' },
  { title: 'Prestar', value: 'PRESTAR', color: 'warning' },
  { title: 'Devolver', value: 'DEVOLVER', color: 'teal' }
]

export function buscarEnCatalogo(catalogo, valor) {
  return catalogo.find((elemento) => elemento.value === valor) ?? { title: valor ?? '', value: valor, color: 'grey' }
}

export function nombreCompleto(fila) {
  if (!fila) {
    return ''
  }

  return [fila.apellido_paterno, fila.apellido_materno, fila.nombres]
    .map((parte) => (parte ?? '').trim())
    .filter(Boolean)
    .join(' ')
}

// Se leen las partes del texto a mano para no desplazar el día por la zona horaria del navegador.
export function fecha(valor) {
  const coincidencia = /^(\d{4})-(\d{2})-(\d{2})/.exec(valor ?? '')

  return coincidencia ? `${coincidencia[3]}/${coincidencia[2]}/${coincidencia[1]}` : ''
}

export function fechaHora(valor) {
  const coincidencia = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(valor ?? '')

  return coincidencia
    ? `${coincidencia[3]}/${coincidencia[2]}/${coincidencia[1]} ${coincidencia[4]}:${coincidencia[5]}`
    : ''
}

export function periodoLaboral(fila) {
  const ingreso = fecha(fila?.fecha_ingreso)
  const retiro = fecha(fila?.fecha_retiro)

  if (ingreso && retiro) return `${ingreso} al ${retiro}`
  if (ingreso) return `Desde ${ingreso}`
  if (retiro) return `Hasta ${retiro}`

  return ''
}

export function ubicacionTexto(fila) {
  if (!fila) {
    return ''
  }

  const partes = [
    fila.ambiente,
    fila.estante && `Estante ${fila.estante}`,
    fila.archivador && `Archivador ${fila.archivador}`,
    fila.caja && `Caja ${fila.caja}`,
    fila.carpeta && `Carpeta ${fila.carpeta}`
  ].filter(Boolean)

  return partes.join(' / ')
}

// Convierte cadenas vacías en null antes de enviar un formulario.
export function limpiarFormulario(datos) {
  return Object.fromEntries(
    Object.entries(datos).map(([clave, valor]) => [
      clave,
      typeof valor === 'string' && valor.trim() === '' ? null : valor
    ])
  )
}

export const reglas = {
  obligatorio: (valor) =>
    (valor !== null && valor !== undefined && String(valor).trim() !== '') || 'Este campo es obligatorio.',
  maximo: (limite) => (valor) =>
    !valor || String(valor).length <= limite || `Máximo ${limite} caracteres.`
}

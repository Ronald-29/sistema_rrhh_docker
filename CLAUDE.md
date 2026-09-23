# Sistema de Archivo RRHH - EMAPA

Sistema para organizar e inventariar los expedientes personales (files) del Archivo de
Recursos Humanos de EMAPA (Empresa de Apoyo a la Producción de Alimentos, Bolivia).
Toda la comunicación, el código y los mensajes al usuario van en español.

## Tecnología

- Backend: PHP 8 sin framework, PDO + PostgreSQL, vlucas/phpdotenv (composer).
- Rutas: `backend/public/index.php` (un solo archivo con `if` por ruta).
- Código: `backend/src/Controllers`, `Models`, `Middleware`, `Services`, `Views` (plantillas HTML).
- Conexión: `backend/config/database.php` (devuelve `$pdo`, lee `backend/.env`, fija la zona
  horaria `America/La_Paz` en la sesión de PostgreSQL).
- Base de datos: `database/esquema.sql` (pg_dump del esquema actual), `database/datos_iniciales.sql`
  (roles) y `database/migraciones/` (cambios numerados, ya incluidos en esquema.sql).
- Frontend: `frontend/`, **Vue 3.5 + Vuetify 4.0.4 (versión fija) + vue-router 5 + Vite 8**,
  JavaScript (sin TypeScript) y sin pinia. Ver la sección Frontend.
- Entorno de trabajo del equipo (servidor Linux): PHP 8.5.4, Composer 2.9.5, Node 24.14.1,
  Vuetify 4.0.4, PostgreSQL 18.4. En local hay PHP 8.5.10 y PostgreSQL 16.4. En Linux las rutas
  distinguen mayúsculas: los `require` deben coincidir exactamente con el nombre del archivo.
- Sesión PHP con roles: `Administrador`, `Recursos Humanos`, `Consulta` (`AuthMiddleware::verificarRol`).
- Saltos de línea: git tiene `core.autocrlf=true` (guarda LF, entrega CRLF). Los archivos PHP
  existentes están en CRLF en disco; al editarlos con `sed -i` quedan en LF, reconvertirlos.
- PHP no tiene las extensiones `zip`, `gd` ni `intl` (sí `zlib`): no se puede usar PhpSpreadsheet
  ni dompdf. Por eso el Excel lo arma `Services/GeneradorExcel.php` y el PDF sale del navegador.

## Convenciones

- Los métodos de los controladores están en español: `listar`, `crear`, `obtenerPorId`,
  `actualizar`, `devolver`, `cambiarEstado`, `listarPorExpediente`. NO usar index/store/show/update.
- Respuestas JSON con `success` y `message`, usando `JSON_UNESCAPED_UNICODE`. Los 401 del
  middleware agregan `codigo`: `SESION_REQUERIDA` o `USUARIO_DESACTIVADO`.
- Operaciones que modifican datos: transacción + registro en auditoría (`Auditoria::registrar`).
  Acciones permitidas por el CHECK: CREAR, MODIFICAR, ELIMINAR, PRESTAR, DEVOLVER.
- Cambios de esquema: nuevo archivo en `database/migraciones/NNN_descripcion.sql`, aplicarlo y
  regenerar `database/esquema.sql` con `pg_dump --schema-only --no-owner --no-privileges`
  (quitar las líneas `\restrict` / `\unrestrict`).
- Formatos del inventario (nombre completo, periodo laboral, ubicación, orden sin acentos):
  `Services/FormatoInventario.php`, compartido por Excel, informe y rótulos.

## Requerimiento de EMAPA (términos de referencia)

1. Revisión física de todos los files personales del Archivo de RRHH.
   -> `expedientes.revisado`, `revisado_por`, `fecha_revision`; `PUT /api/expedientes/{id}/revision`
2. Clasificar en **Activos** (relación laboral vigente) y **Pasivos** (ex servidores públicos).
   -> `personal.estado_laboral`
3. Identificar cada expediente: nombres completos y datos para individualizarlo.
4. Organizar activos y pasivos para facilitar su ubicación y consulta.
5. Inventario detallado con mínimo: número correlativo, nombre completo, condición
   (activo/pasivo), cargo, gestión o periodo laboral, ubicación física, observaciones.
   -> `GET /api/reportes/inventario` (periodo = `fecha_ingreso` / `fecha_retiro`)
6. Registrar expedientes incompletos, deteriorados o con observaciones.
   -> `expedientes.estado_expediente` (INCOMPLETO, DETERIORADO, OBSERVADO) y `observaciones`
7. Rotular carpetas, cajas y archivadores. -> `GET /api/reportes/rotulos`
8. Informe final con el inventario consolidado de activos y pasivos y las observaciones.
   -> `GET /api/reportes/inventario/excel` y `GET /api/reportes/informe-final`

Requisito adicional: registrar quién entrega el expediente/documento y a quién, y en la
devolución quién lo devuelve y quién lo recibe. -> Módulo de préstamos.

## Módulo de préstamos

- `POST /api/prestamos`: `id_expediente` O `id_documento` (no ambos), `entregado_por`,
  `recibido_por` (obligatorios), `motivo`, `observaciones_entrega`. Fecha/hora automática.
- `PUT /api/prestamos/{id}/devolucion`: `devuelto_por`, `recibido_devolucion_por`
  (obligatorios), `observaciones_devolucion`. Fecha/hora automática.
- `GET /api/prestamos?estado=PRESTADO|DEVUELTO`, `GET /api/prestamos/{id}`.
- Reportes: `GET /api/reportes/prestamos/pendientes`, `GET /api/reportes/prestamos/resumen`.
- Validaciones: no prestar un expediente ya prestado ni uno con documentos prestados;
  no prestar un documento cuyo expediente está prestado.

## Revisión física (punto 1)

- `PUT /api/expedientes/{id}/revision` (Administrador, RRHH): `revisado_por` obligatorio;
  `estado_expediente` y `observaciones` opcionales (si no se envían se conservan).
  No permite revisar un expediente prestado ni uno ya revisado (409).
- `DELETE /api/expedientes/{id}/revision` (solo Administrador): anula la revisión.
- `GET /api/expedientes?revisado=true|false`. El resumen de inventario incluye
  `total_revisados` y `total_pendientes_revision`.

## Usuarios y sesión

- `AuthMiddleware::usarConexion($pdo)` (en `index.php`) hace que cada petición relea el usuario
  de la base de datos: si fue desactivado responde 401 `USUARIO_DESACTIVADO` y destruye la
  sesión; si le cambiaron el rol, el nuevo rol se aplica de inmediato. Si la consulta falla,
  se conserva la sesión para no dejar a todos fuera por un problema pasajero.
- Solo Administrador: `GET/POST /api/usuarios`, `GET/PUT /api/usuarios/{id}`,
  `PUT /api/usuarios/{id}/estado` (ACTIVO/INACTIVO), `PUT /api/usuarios/{id}/password`
  (asignar contraseña nueva) y `GET /api/roles`.
- Cualquier rol: `PUT /api/mi-password` con `password_actual` y `password`.
- Reglas: nombre de usuario único de 3 a 50 caracteres (`[A-Za-z0-9._-]`), contraseña de 8+
  caracteres, no cambiar el rol propio, no desactivarse a sí mismo y no dejar el sistema sin
  un administrador activo. La auditoría de contraseñas guarda solo la descripción, nunca el hash.
- Las consultas de gestión de `Models/Usuario.php` nunca devuelven `password_hash`
  (`buscarPorNombreUsuario` sí, porque lo usa el login).

## Entregables (puntos 7 y 8)

Lógica en `Services/InformeInventario.php`; plantillas en `src/Views/`.

- `GET /api/reportes/inventario/excel`: .xlsx con hojas Resumen, Activos, Pasivos, Observaciones.
- `GET /api/reportes/informe-final`: HTML imprimible (Imprimir -> Guardar como PDF) con resumen,
  avance de revisión, inventario de activos y pasivos, observaciones, préstamos pendientes y firmas.
- `GET /api/reportes/rotulos?tipo=carpeta|caja|archivador&estado_laboral=&id_ubicacion=`:
  HTML imprimible. Cajas y archivadores se agrupan por los textos de `ubicaciones`
  (ambiente, estante, archivador, caja); los expedientes sin ubicación solo salen en carpetas.
- Sin sesión: `php backend/scripts/generar_informe.php` escribe los 5 archivos en
  `backend/storage/informes/AAAA-MM-DD_HHMM/` (carpeta ignorada por git).

## Frontend

- Estructura `frontend/src`: `services/api.js` (fetch a `/api`, lanza `ErrorApi` con el `message`
  del backend), `services/sesion.js` (usuario y permisos: `sesion.puedeEditar`,
  `sesion.esAdministrador`), `router/index.js` (guard de sesión y rutas solo para Administrador),
  `views/` (una por pantalla), `components/formularios/` (un diálogo por formulario, sobre
  `DialogoFormulario.vue`), `utils/formato.js` (catálogos de estados y formatos de fecha/nombre).
- Pantallas: login, inicio (resumen y avance de revisión), expedientes (lista y detalle con
  documentos, revisión, préstamos y devoluciones), personal, ubicaciones, préstamos, reportes
  (Excel, informe, rótulos, inventario, pendientes), usuarios y auditoría (las dos últimas solo
  Administrador). "Cambiar mi contraseña" está en el menú del usuario, para todos los roles.
- Los permisos en pantalla repiten los de `index.php`: Consulta no ve botones de edición.
- Las listas se cargan completas y se filtran en el navegador (búsqueda y filtros de `v-data-table`).
- Sesión: cookie PHP, por eso frontend y API deben compartir origen. En desarrollo Vite redirige
  `/api` al backend (`VITE_BACKEND_URL`, por defecto `http://localhost:8000`). En producción,
  servir `frontend/dist` y enviar `/api/*` a `backend/public/index.php` en el mismo dominio;
  las demás rutas deben responder `index.html` (historial HTML5 de vue-router).
- Vuetify 4 cambia cosas respecto de la 3 (verificado en `node_modules`):
  - Tema por defecto `system` (oscuro según el sistema operativo): se fija `light`.
  - Tipografía MD3: `text-headline-small`, `text-title-large`, `text-body-medium`, etc.
    (`text-h5`, `text-caption` y similares ya no existen). Tampoco existe `fill-height`.
  - `<v-row dense>` está obsoleto: usar `density="comfortable"`.
  - En los slots `item` de `v-autocomplete`/`v-select` el objeto llega directo (`item.subtitle`),
    no en `item.raw`.
- Si un formulario se monta con `v-if` junto con abrir el diálogo, su `watch` de apertura
  necesita `{ immediate: true }` (ver `DevolucionFormulario.vue`).

## Estado y próximos pasos

Revisado el 2026-09-19. Backend y frontend completos, incluida la gestión de usuarios.
Pruebas de navegador en el scratchpad de la sesión (Playwright + Edge, sobre una copia de la BD):
`prueba.mjs` (21 pasos del flujo de archivo) y `prueba_usuarios.mjs` (13 pasos de usuarios,
contraseñas y desactivación). Las dos en verde; antes de cada corrida se ejecuta `reiniciar_bd.sh`.

Pendiente:
1. Instalación en el servidor de EMAPA: publicar `frontend/dist` y enviar `/api/*` a
   `backend/public/index.php` en el mismo dominio (ver la sección Frontend). Falta saber
   qué servidor web usan (Apache o Nginx).
2. Fechas antiguas: antes de fijar `America/La_Paz` en la conexión, PostgreSQL usaba
   `Europe/Paris`, así que los préstamos y la auditoría registrados hasta el 2026-09-16
   tienen la hora adelantada (5 o 6 horas). Son datos de prueba; corregirlos o borrarlos
   antes de cargar datos reales.
3. Validar con EMAPA el formato de los rótulos (tamaño de etiqueta, datos que quieren ver)
   y del informe final (encabezado institucional, firmas).
4. Los errores 500 no se registran en ningún log (`catch (Throwable $e)` sin `error_log`).

## Cómo ejecutar y probar

`INSTALACION.md` (raíz del repositorio) tiene la guía completa para instalar el sistema en
otra computadora: programas, base de datos, `.env`, primer administrador, frontend, uso
diario, ejemplos de Apache y Nginx (sin probar), verificación y problemas frecuentes.

```bash
# Terminal 1: backend (127.0.0.1, no localhost: si PHP escucha solo en IPv6 el proxy da 502)
php -S 127.0.0.1:8000 -t backend/public
# Terminal 2: frontend (http://localhost:5173)
cd frontend && npm install && npm run dev
# Compilar para producción (genera frontend/dist)
cd frontend && npm run build
```

API con curl:

```bash
curl -c cookies.txt -X POST http://localhost:8000/api/login -H "Content-Type: application/json" -d "{\"nombre_usuario\":\"...\",\"password\":\"...\"}"
curl -b cookies.txt http://localhost:8000/api/prestamos
curl -b cookies.txt -o inventario.xlsx http://localhost:8000/api/reportes/inventario/excel
```

Para no ensuciar la BD real: `createdb -T sistema_archivo_rrhh sistema_archivo_rrhh_pruebas`
(sin conexiones abiertas a la original) y apuntar una copia del backend a esa BD en `.env`.


# Solo prueba
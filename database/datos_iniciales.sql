-- Datos mínimos para una base de datos nueva.
-- Orden de instalación:
--   1. psql -d sistema_archivo_rrhh -f database/esquema.sql
--   2. psql -d sistema_archivo_rrhh -f database/datos_iniciales.sql
--   3. php backend/scripts/crear_admin.php
--
-- esquema.sql ya incluye las migraciones de database/migraciones.

-- Se usa public.roles porque esquema.sql deja vacío el search_path de la sesión.
INSERT INTO public.roles (nombre, descripcion)
VALUES
    ('Administrador', 'Control completo del sistema'),
    ('Recursos Humanos', 'Gestion de personal, expedientes, documentos, prestamos, devoluciones y reportes'),
    ('Consulta', 'Consulta de informacion sin permisos de modificacion')
ON CONFLICT (nombre) DO NOTHING;

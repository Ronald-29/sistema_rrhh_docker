# Instalación del Sistema de Archivo RRHH - EMAPA

Guía para poner el sistema en funcionamiento en otra computadora. Los pasos fueron
probados en Windows; donde cambia el comando para Linux, se indica.

Tiempo estimado: 30 a 45 minutos, la mayor parte esperando descargas.

---

## 1. Programas necesarios

Instale primero estos programas. Entre paréntesis, la versión usada en las pruebas.

| Programa | Versión mínima | Para qué sirve | Dónde se descarga |
|---|---|---|---|
| PHP | 8.1 (probado con 8.5) | Ejecuta el backend | https://windows.php.net/download (Windows) o `sudo apt install php8.3-cli` |
| Composer | 2.x (probado con 2.10) | Instala las librerías de PHP | https://getcomposer.org/download |
| PostgreSQL | 14 (probado con 16 y 18) | Base de datos | https://www.postgresql.org/download |
| Node.js | 22.12 (probado con 24) | Compila el frontend | https://nodejs.org (versión LTS) |
| Git | cualquiera | Copiar el proyecto | https://git-scm.com/downloads |

**Extensiones de PHP**: se necesitan `pdo_pgsql`, `pgsql` y `mbstring`. Para comprobarlo:

```bash
php -m
```

Las tres deben aparecer en la lista. En Windows se activan quitando el `;` del inicio de
estas líneas en el archivo `php.ini`:

```ini
extension=pdo_pgsql
extension=pgsql
extension=mbstring
```

En Linux: `sudo apt install php8.3-pgsql php8.3-mbstring`.

No hacen falta las extensiones `zip`, `gd` ni `intl`: el sistema genera el Excel y los
informes sin ellas.

Para verificar que todo quedó instalado:

```bash
php -v
composer -V
node -v
psql --version
```

---

## 2. Copiar el proyecto

```bash
git clone <URL-del-repositorio> sistema_archivo_rrhh
cd sistema_archivo_rrhh
```

Si copia la carpeta con un pendrive en lugar de usar git, **no copie** `backend/vendor`,
`frontend/node_modules` ni `frontend/dist`: esas carpetas se generan en los pasos siguientes
y pesan mucho.

---

## 3. Crear la base de datos

Reemplace `postgres` por su usuario de PostgreSQL si usa otro. Le pedirá la contraseña.

```bash
createdb -h localhost -U postgres sistema_archivo_rrhh
psql -h localhost -U postgres -d sistema_archivo_rrhh -f database/esquema.sql
psql -h localhost -U postgres -d sistema_archivo_rrhh -f database/datos_iniciales.sql
```

`esquema.sql` crea las 8 tablas y `datos_iniciales.sql` carga los tres roles
(Administrador, Recursos Humanos y Consulta).

**En Windows**, si aparece un error de codificación con las tildes, ejecute antes:

```bash
set PGCLIENTENCODING=UTF8
```

Para comprobar que quedó bien:

```bash
psql -h localhost -U postgres -d sistema_archivo_rrhh -c "\dt"
```

Deben aparecer 8 tablas: auditoria, documentos, expedientes, personal, prestamos, roles,
ubicaciones y usuarios.

### Si quiere llevar los datos de otra computadora

En la computadora que ya tiene datos:

```bash
pg_dump -h localhost -U postgres sistema_archivo_rrhh > respaldo.sql
```

En la computadora nueva, en lugar de los tres comandos anteriores:

```bash
createdb -h localhost -U postgres sistema_archivo_rrhh
psql -h localhost -U postgres -d sistema_archivo_rrhh -f respaldo.sql
```

---

## 4. Configurar el backend

```bash
cd backend
composer install
```

Luego cree el archivo de configuración a partir del ejemplo:

```bash
copy .env.example .env        # Windows
cp .env.example .env          # Linux
```

Abra `backend/.env` y complete los datos de su PostgreSQL:

```ini
APP_ENV=development

DB_HOST=localhost
DB_PORT=5432
DB_NAME=sistema_archivo_rrhh
DB_USER=postgres
DB_PASSWORD=la-contraseña-de-postgres
```

Este archivo **no se guarda en el repositorio** porque contiene la contraseña: cada
computadora tiene el suyo.

---

## 5. Crear el primer usuario administrador

Desde la carpeta `backend`:

```bash
php scripts/crear_admin.php
```

El script pide nombres, apellidos, nombre de usuario y contraseña. Use una contraseña de
al menos 8 caracteres. Con ese usuario podrá entrar al sistema y crear los demás desde la
pantalla **Usuarios**.

---

## 6. Instalar el frontend

Desde la raíz del proyecto:

```bash
cd frontend
npm install
```

La descarga tarda varios minutos la primera vez.

---

## 7. Usar el sistema (modo desarrollo)

Se necesitan **dos terminales abiertas al mismo tiempo**, desde la raíz del proyecto.

**Terminal 1, backend:**

```bash
php -S 127.0.0.1:8000 -t backend/public
```

**Terminal 2, frontend:**

```bash
cd frontend
npm run dev
```

Abra **http://localhost:5173** en el navegador e ingrese con el usuario del paso 5.

Para detener cada servidor, presione `Ctrl + C` en su terminal.

> Use `127.0.0.1` y no `localhost` en el comando del backend: si PHP queda escuchando solo
> en IPv6, la aplicación muestra el error 502.

---

## 8. Instalación para uso real (varios usuarios en la red)

Para que otras personas usen el sistema desde sus computadoras hace falta un servidor web
(Apache o Nginx). Primero compile el frontend:

```bash
cd frontend
npm run build
```

Eso genera la carpeta `frontend/dist` (unos 5 MB) con los archivos que se publican.

**Regla importante:** el frontend y la API deben quedar en el **mismo dominio**, porque la
sesión usa una cookie de PHP. Es decir:

- `http://servidor/` debe entregar `frontend/dist/index.html`
- `http://servidor/api/...` debe llegar a `backend/public/index.php`
- cualquier otra dirección (por ejemplo `http://servidor/expedientes`) debe entregar también
  `index.html`, porque las pantallas las maneja el navegador

### Ejemplo con Apache

```apache
<VirtualHost *:80>
    ServerName archivo-rrhh.emapa.local
    DocumentRoot /var/www/sistema_archivo_rrhh/frontend/dist

    Alias /api /var/www/sistema_archivo_rrhh/backend/public/index.php

    <Directory /var/www/sistema_archivo_rrhh/backend/public>
        Require all granted
    </Directory>

    <Directory /var/www/sistema_archivo_rrhh/frontend/dist>
        Require all granted
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule . /index.html [L]
    </Directory>
</VirtualHost>
```

Necesita `sudo a2enmod rewrite` y PHP habilitado en Apache (`libapache2-mod-php`).

### Ejemplo con Nginx

```nginx
server {
    listen 80;
    server_name archivo-rrhh.emapa.local;
    root /var/www/sistema_archivo_rrhh/frontend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /var/www/sistema_archivo_rrhh/backend/public/index.php;
    }
}
```

> Estos dos ejemplos **todavía no fueron probados** en un servidor real; sirven como punto de
> partida y deben ajustarse a la configuración de EMAPA (rutas, dominio y versión de PHP).
> Además, el servidor debe poder leer y escribir `backend/storage`.

---

## 9. Comprobar que quedó bien instalado

Con las dos terminales encendidas, entre al sistema y revise:

1. **Ingreso:** el usuario y la contraseña del paso 5 funcionan.
2. **Inicio:** se ve el resumen (al principio todo en cero).
3. **Personal:** se puede registrar una persona.
4. **Expedientes:** se puede registrar un expediente para esa persona.
5. **Préstamos:** se puede registrar un préstamo y su devolución.
6. **Reportes:** el botón "Descargar Excel" baja un archivo que abre en Excel, y
   "Abrir informe imprimible" muestra el informe en otra pestaña.
7. **Usuarios:** aparece en el menú y permite crear otro usuario.

---

## 10. Problemas frecuentes

| Qué aparece | Causa y solución |
|---|---|
| "No se pudo conectar con el servidor" en la pantalla | La terminal del backend no está encendida, o se cerró. Vuelva al paso 7. |
| Error 502 al ingresar | El backend se inició con `localhost`. Deténgalo y use `php -S 127.0.0.1:8000 -t backend/public`. |
| "Port 5173 is in use" | Quedó otra ventana del frontend abierta. Ciérrela, o use la dirección que muestre la terminal (por ejemplo 5174). |
| "could not find driver" al abrir el sistema | Falta activar `pdo_pgsql` en `php.ini` (paso 1). |
| "password authentication failed" | La contraseña de `backend/.env` no coincide con la de PostgreSQL (paso 4). |
| "database ... does not exist" | Falta crear la base de datos (paso 3), o el nombre en `.env` está mal escrito. |
| Las tildes salen mal al cargar los archivos .sql | Ejecute `set PGCLIENTENCODING=UTF8` antes de los comandos `psql` (paso 3). |
| Las horas se guardan adelantadas | El sistema fija la zona horaria `America/La_Paz` al conectarse; si ve horas raras en registros viejos, son de antes de esa corrección. |
| `npm install` falla por permisos | No ejecute la terminal como administrador; instale Node.js desde el instalador oficial. |

---

## 11. Respaldos

Conviene copiar la base de datos periódicamente:

```bash
pg_dump -h localhost -U postgres sistema_archivo_rrhh > respaldo_2026-09-19.sql
```

Guarde también el archivo `backend/.env`, porque no está en el repositorio.

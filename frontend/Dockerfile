# Imagen web de producción del Sistema de Archivo RRHH:
# compila el frontend con Node y lo sirve con Nginx.

#==== Etapa compilacion: Node compila Vue a HTML, CSS y JS ====
FROM node:24 AS compilacion

WORKDIR /app

# Primero solo los archivos de dependencias, por la caché.
COPY package.json package-lock.json ./
RUN npm ci

# Luego el código, y la compilación. El resultado queda en /app/dist.
COPY . .
RUN npm run build

#==== Etapa produccion: solo Nginx y los archivos compilados ====
FROM nginx:stable-alpine AS produccion

# Nuestra configuración reemplaza la de ejemplo que trae la imagen.
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=compilacion /app/dist /usr/share/nginx/html
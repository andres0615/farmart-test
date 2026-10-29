# syntax=docker/dockerfile:1

# ------------------------------------------------------------
# Etapa 1: dependencias (se cachea mientras no cambie el lockfile)
# ------------------------------------------------------------
FROM node:22-alpine AS deps
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

# ------------------------------------------------------------
# Etapa 2: build de Vite
# VITE_API_URL se incrusta en el bundle en tiempo de build, por lo
# que debe ser una URL accesible desde el NAVEGADOR del usuario.
# ------------------------------------------------------------
FROM deps AS build
ARG VITE_API_URL=http://localhost:8000/api
ENV VITE_API_URL=${VITE_API_URL}
COPY . .
RUN npm run build

# ------------------------------------------------------------
# Etapa 3: runtime con Nginx sin privilegios (usuario no root, puerto 8080)
# ------------------------------------------------------------
FROM nginxinc/nginx-unprivileged:1.27-alpine AS app

COPY --chown=101:101 nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build --chown=101:101 /app/dist /usr/share/nginx/html

# La imagen base ya corre como UID 101 (nginx); lo dejamos explícito.
USER 101

EXPOSE 8080

HEALTHCHECK --interval=10s --timeout=5s --retries=5 --start-period=5s \
  CMD wget -qO- http://127.0.0.1:8080/ >/dev/null || exit 1
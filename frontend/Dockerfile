# ── Stage 1: build ────────────────────────────────────────────────────────────
FROM node:20-alpine AS builder

WORKDIR /app

# Instalar dependencias primero (aprovecha caché de capas)
COPY package.json package-lock.json* ./
RUN npm ci

# Copiar fuentes y compilar
# Se sobreescribe outDir para que el artefacto quede dentro del contexto Docker
COPY . .
RUN npx vite build --outDir /dist

# ── Stage 2: runtime ───────────────────────────────────────────────────────────
FROM nginx:1.27-alpine

# Plantilla de configuración: Railway inyecta $PORT en tiempo de ejecución
COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template

# Artefactos de la build
COPY --from=builder /dist /usr/share/nginx/html

CMD ["nginx", "-g", "daemon off;"]

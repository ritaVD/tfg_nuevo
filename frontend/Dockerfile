# ── Stage 1: build ────────────────────────────────────────────────────────────
FROM node:20-alpine AS builder

WORKDIR /app

# Instalar dependencias primero (aprovecha caché de capas)
COPY package.json package-lock.json* ./
RUN npm ci

# Copiar fuentes y compilar
# Se sobreescribe outDir para que el artefacto quede dentro del contexto Docker
COPY . .
RUN npx tsc -b && npx vite build --outDir /dist

# ── Stage 2: runtime ───────────────────────────────────────────────────────────
FROM nginx:1.27-alpine

# Configuración Nginx para SPA (React Router)
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Artefactos de la build
COPY --from=builder /dist /usr/share/nginx/html

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]

# Multi-stage build untuk Svelte frontend

# Stage 1: Build
FROM node:20-alpine AS builder

WORKDIR /app

# Copy package files
COPY package*.json ./
COPY vite.config.js ./
COPY svelte.config.js ./
COPY jsconfig.json ./

# Install dependencies
RUN npm ci

# Copy source code
COPY src ./src
COPY public ./public
COPY index.html ./

# Build untuk production
RUN npm run build

# Stage 2: Production - Serve dengan Nginx
FROM nginx:alpine

# Copy built files dari builder
COPY --from=builder /app/dist /usr/share/nginx/html

# Copy custom nginx config untuk SPA (Single Page Application)
RUN echo 'server { \
    listen 80; \
    server_name _; \
    root /usr/share/nginx/html; \
    index index.html; \
    location / { \
        try_files $uri $uri/ /index.html; \
    } \
    location /api { \
        return 404; \
    } \
}' > /etc/nginx/conf.d/default.conf

# Expose port
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]


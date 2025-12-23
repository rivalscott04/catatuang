# Quick Start - Docker Setup

Panduan cepat untuk menjalankan semua services dengan Docker.

## 🚀 Development (Localhost)

### 1. Setup Environment

```bash
# Copy environment template
cp docker/env.example docker/.env

# Edit docker/.env - minimal set:
# INTERNAL_API_KEY=your-random-secret-key-here
# DB_PASSWORD=your-db-password
```

### 2. Start Services

```bash
# Start semua services (SQLite untuk n8n)
docker-compose up -d

# Atau dengan PostgreSQL untuk n8n
docker-compose --profile postgres up -d
```

### 3. Setup Backend

```bash
# Generate app key
docker-compose exec backend php artisan key:generate

# Run migrations
docker-compose exec backend php artisan migrate
```

### 4. Start Frontend (Development - Hot Reload)

```bash
# Start frontend dengan hot reload
docker-compose --profile dev up -d frontend
```

### 5. Akses Services

- **Frontend**: http://localhost:5173 (development dengan hot reload)
- **Backend**: http://localhost:8000
- **n8n**: http://localhost:5678
- **GoWA**: http://localhost:3000

## 🏭 Production (dengan Domain)

### 1. Setup Domain & SSL

```bash
# Generate SSL certificates (Let's Encrypt)
sudo certbot certonly --standalone -d api.yourdomain.com
sudo certbot certonly --standalone -d n8n.yourdomain.com
sudo certbot certonly --standalone -d gowa.yourdomain.com

# Copy certificates
sudo cp /etc/letsencrypt/live/api.yourdomain.com/fullchain.pem docker/nginx/ssl/api.yourdomain.com.crt
sudo cp /etc/letsencrypt/live/api.yourdomain.com/privkey.pem docker/nginx/ssl/api.yourdomain.com.key
# Ulangi untuk n8n dan gowa
```

### 2. Update Configuration

**docker/.env:**
```env
APP_ENV=production
APP_DEBUG=false

BACKEND_URL=https://api.yourdomain.com
GOWA_URL=https://gowa.yourdomain.com
N8N_WEBHOOK_URL=https://n8n.yourdomain.com

N8N_DB_TYPE=postgresdb
N8N_BASIC_AUTH_ACTIVE=true
```

**docker/nginx/conf.d/default.conf:**
- Ganti semua `yourdomain.com` dengan domain kamu

### 3. Start Production

```bash
# Start dengan nginx, postgres, dan frontend production
docker-compose --profile postgres --profile production up -d
```

### 4. Akses Production

- **Frontend**: https://yourdomain.com
- **Backend API**: https://api.yourdomain.com
- **n8n**: https://n8n.yourdomain.com
- **GoWA**: https://gowa.yourdomain.com

## 📝 Command Cheat Sheet

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f

# Restart service
docker-compose restart backend

# Execute command in container
docker-compose exec backend php artisan migrate

# Rebuild after code changes
docker-compose up -d --build

# Auto-rebuild frontend saat file berubah
docker-compose --profile production watch frontend-prod
```

## 🔍 Troubleshooting

**Port sudah digunakan?**
- Edit `docker/.env` dan ubah port (BACKEND_PORT, N8N_PORT, dll)

**Database connection error?**
- Check `docker-compose ps` - pastikan db container running
- Check credentials di `docker/.env`

**n8n tidak bisa connect ke backend?**
- Pastikan `BACKEND_URL` di n8n environment variables benar
- Untuk Docker: `http://backend` (service name)
- Untuk production: `https://api.yourdomain.com`

Lihat `DOCKER_SETUP.md` untuk dokumentasi lengkap.


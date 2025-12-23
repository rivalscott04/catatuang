# Docker Setup Guide - WA Finance Bot

Panduan lengkap setup Docker untuk Backend Laravel, n8n, dan GoWA dengan konfigurasi development dan production (domain).

## 📋 Prerequisites

- Docker >= 20.10
- Docker Compose >= 2.0
- Domain dengan DNS A record (untuk production)
- SSL Certificate (untuk production HTTPS)

## 🚀 Quick Start (Development)

### 1. Setup Environment

```bash
# Copy environment file
cp docker/env.example docker/.env

# Edit docker/.env sesuai kebutuhan
# Minimal set:
# - INTERNAL_API_KEY (ganti dengan random string)
# - DB_PASSWORD
```

### 2. Start Services

```bash
# Start semua services (tanpa nginx, tanpa postgres untuk n8n)
docker-compose up -d

# Atau start dengan postgres untuk n8n (production-like)
docker-compose --profile postgres up -d
```

### 3. Setup Backend

```bash
# Masuk ke container backend
docker-compose exec backend bash

# Di dalam container:
composer install
php artisan key:generate
php artisan migrate

# Atau dari luar container:
docker-compose exec backend php artisan key:generate
docker-compose exec backend php artisan migrate
```

### 4. Start Frontend (Development)

```bash
# Start frontend dengan hot reload
docker-compose --profile dev up -d frontend
```

### 5. Akses Services

- **Frontend**: http://localhost:5173 (development dengan hot reload)
- **Backend API**: http://localhost:8000
- **n8n UI**: http://localhost:5678
- **GoWA**: http://localhost:3000 (jika sudah dikonfigurasi)

## 🏭 Production Setup dengan Domain

### 1. Persiapkan Domain

Pastikan DNS sudah dikonfigurasi:
```
A Record:
- api.yourdomain.com    -> IP Server
- n8n.yourdomain.com     -> IP Server
- gowa.yourdomain.com    -> IP Server
```

### 2. Setup SSL Certificate

**Opsi A: Let's Encrypt (Recommended)**
```bash
# Install certbot
sudo apt-get update
sudo apt-get install certbot

# Generate certificates
sudo certbot certonly --standalone -d api.yourdomain.com
sudo certbot certonly --standalone -d n8n.yourdomain.com
sudo certbot certonly --standalone -d gowa.yourdomain.com

# Copy certificates ke docker/nginx/ssl/
sudo cp /etc/letsencrypt/live/api.yourdomain.com/fullchain.pem docker/nginx/ssl/api.yourdomain.com.crt
sudo cp /etc/letsencrypt/live/api.yourdomain.com/privkey.pem docker/nginx/ssl/api.yourdomain.com.key
# Ulangi untuk n8n dan gowa
```

**Opsi B: Self-signed (Development/Testing)**
```bash
# Buat directory
mkdir -p docker/nginx/ssl

# Generate self-signed certificates
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/nginx/ssl/api.yourdomain.com.key \
  -out docker/nginx/ssl/api.yourdomain.com.crt \
  -subj "/CN=api.yourdomain.com"

# Ulangi untuk n8n dan gowa
```

### 3. Update Environment untuk Production

Edit `docker/.env` (copy dari `docker/env.example`):
```env
APP_ENV=production
APP_DEBUG=false

# Domain URLs
BACKEND_URL=https://api.yourdomain.com
GOWA_URL=https://gowa.yourdomain.com
N8N_WEBHOOK_URL=https://n8n.yourdomain.com

# n8n dengan PostgreSQL
N8N_DB_TYPE=postgresdb

# n8n Authentication
N8N_BASIC_AUTH_ACTIVE=true
N8N_BASIC_AUTH_USER=admin
N8N_BASIC_AUTH_PASSWORD=strong-password-here

# Strong API Key
INTERNAL_API_KEY=very-secure-random-string-min-32-chars
```

### 4. Update Nginx Configuration

Edit `docker/nginx/conf.d/default.conf`:
- Ganti semua `yourdomain.com` dengan domain kamu
- Frontend akan di serve di root domain (yourdomain.com)
- Backend API di subdomain (api.yourdomain.com)
- Pastikan path SSL certificate benar

### 5. Start Production Services

```bash
# Start dengan nginx dan postgres
docker-compose --profile postgres --profile production up -d

# Check logs
docker-compose logs -f
```

### 6. Build Frontend untuk Production

```bash
# Build frontend (otomatis saat start dengan profile production)
docker-compose --profile production build frontend-prod
```

### 7. Update n8n Environment Variables

Setelah n8n running, update environment variables di n8n UI:
1. Buka https://n8n.yourdomain.com
2. Settings > Environment Variables
3. Update:
   - `BACKEND_URL=https://api.yourdomain.com`
   - `GOWA_URL=https://gowa.yourdomain.com`
   - `INTERNAL_API_KEY` (sama dengan di docker/.env)

### 8. Update GoWA Webhook

Di GoWA admin panel, set webhook URL:
- URL: `https://n8n.yourdomain.com/webhook/wa-in`
- Method: POST

## 📁 Struktur File Docker

```
.
├── docker-compose.yml          # Main compose file
├── Dockerfile                  # Frontend production build
├── Dockerfile.dev              # Frontend development (hot reload)
├── docker/
│   ├── env.example             # Environment template
│   ├── .env                    # Environment (gitignored)
│   └── nginx/
│       ├── nginx.conf          # Main nginx config
│       ├── conf.d/
│       │   └── default.conf    # Server blocks
│       ├── ssl/                # SSL certificates
│       └── logs/               # Nginx logs
├── backend/
│   └── Dockerfile              # Backend Laravel image
├── src/                        # Frontend source code (Svelte)
├── package.json                # Frontend dependencies
└── vite.config.js              # Vite config
```

## 🔧 Service Configuration

### Frontend Svelte
- **Port**: 5173 (dev dengan hot reload), 80 internal (prod via nginx)
- **Development**: Vite dev server dengan hot reload
- **Production**: Static files di serve via nginx
- **Volume**: `./src`, `./public` untuk development

### Backend Laravel
- **Port**: 8000 (dev), 80 internal (prod via nginx)
- **Database**: MySQL 8.0
- **Volume**: `./backend` untuk development

### n8n
- **Port**: 5678
- **Database**: SQLite (dev) atau PostgreSQL (prod)
- **Volume**: `n8n_data` untuk workflows dan credentials

### GoWA
- **Port**: 3000
- **Note**: Sesuaikan dengan setup GoWA yang sebenarnya

### MySQL
- **Port**: 3306
- **Volume**: `db_data` untuk persistence

### PostgreSQL (n8n)
- **Port**: 5432 (internal)
- **Volume**: `postgres_data` untuk persistence

## 🔐 Security Best Practices

### 1. Environment Variables
- Jangan commit `.env` ke git
- Gunakan strong passwords
- Rotate API keys secara berkala

### 2. SSL/TLS
- Selalu pakai HTTPS di production
- Update SSL certificates sebelum expired
- Gunakan Let's Encrypt untuk free SSL

### 3. n8n Authentication
- Aktifkan Basic Auth di production
- Gunakan strong password
- Consider OAuth2 untuk lebih secure

### 4. Network
- Jangan expose database ports ke public
- Gunakan internal Docker network
- Firewall rules untuk block unnecessary ports

### 5. Backend API
- API Key authentication sudah diimplementasi
- Consider rate limiting untuk production
- Monitor API usage

## 📊 Monitoring & Logs

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f backend
docker-compose logs -f n8n
docker-compose logs -f nginx
```

### Check Status
```bash
# Service status
docker-compose ps

# Resource usage
docker stats
```

## 🔄 Maintenance

### Backup Database
```bash
# MySQL backup
docker-compose exec db mysqldump -u wa_finance -ppassword wa_finance > backup.sql

# PostgreSQL backup (n8n)
docker-compose exec postgres pg_dump -U n8n n8n > n8n_backup.sql
```

### Update Services
```bash
# Pull latest images
docker-compose pull

# Rebuild containers
docker-compose up -d --build

# Auto-rebuild frontend saat file berubah (production)
docker-compose --profile production watch frontend-prod
```

### Auto-Rebuild Frontend

Frontend akan auto-rebuild saat ada perubahan file:

```bash
# Start watch mode (auto-rebuild)
docker-compose --profile production watch frontend-prod
```

### Clean Up
```bash
# Stop and remove containers
docker-compose down

# Remove volumes (HATI-HATI: data akan hilang!)
docker-compose down -v
```

## 🐛 Troubleshooting

### Backend tidak bisa connect ke database
```bash
# Check database container
docker-compose ps db

# Check database logs
docker-compose logs db

# Test connection
docker-compose exec backend php artisan tinker
# Di tinker: DB::connection()->getPdo();
```

### n8n tidak bisa connect ke backend
- Check `BACKEND_URL` di n8n environment variables
- Pastikan backend container running
- Check network: `docker network inspect saasuang_wa-finance-network`

### SSL Certificate Error
- Pastikan certificate file ada di `docker/nginx/ssl/`
- Check permission: `chmod 600 docker/nginx/ssl/*.key`
- Verify certificate: `openssl x509 -in docker/nginx/ssl/api.yourdomain.com.crt -text -noout`

### Port Already in Use
```bash
# Check what's using the port
netstat -tulpn | grep :8000

# Atau ubah port di docker/.env
BACKEND_PORT=8001
```

## 📝 Checklist Production

- [ ] Domain DNS configured
- [ ] SSL certificates generated
- [ ] Environment variables set (production)
- [ ] n8n Basic Auth enabled
- [ ] Strong API keys set
- [ ] Database backups configured
- [ ] Monitoring setup
- [ ] Firewall rules configured
- [ ] n8n workflows imported
- [ ] GoWA webhook configured
- [ ] All services tested

## 🚀 Next Steps

1. Setup monitoring (Prometheus, Grafana)
2. Setup automated backups
3. Setup CI/CD pipeline
4. Configure rate limiting
5. Setup logging aggregation (ELK, Loki)
6. Configure auto-scaling (jika perlu)


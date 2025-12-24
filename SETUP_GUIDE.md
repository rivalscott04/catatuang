# Setup Guide - WA Finance Bot

Panduan lengkap setup proyek dari awal sampai running.

## 📋 Prerequisites

1. **PHP >= 8.1** dengan extensions:
   - pdo_mysql
   - mbstring
   - openssl
   - tokenizer
   - xml
   - ctype
   - json
   - bcmath

2. **Composer** (sudah terinstall dari langkah sebelumnya)

3. **MySQL/PostgreSQL** untuk backend database

4. **n8n** (self-hosted atau cloud)
   - Database: SQLite (default) atau PostgreSQL/MySQL untuk production

5. **GoWA** (WhatsApp gateway)

## 🗄️ Database Setup

### Backend (Laravel)
- **Database**: MySQL atau PostgreSQL
- **Nama database**: `wa_finance` (atau sesuai kebutuhan)
- **User**: buat user khusus untuk aplikasi

### n8n
- **Development**: SQLite (default, tidak perlu setup)
- **Production**: PostgreSQL atau MySQL (recommended)

**n8n dengan PostgreSQL (Production):**
```bash
# Environment variables untuk n8n
DB_TYPE=postgresdb
DB_POSTGRESDB_HOST=localhost
DB_POSTGRESDB_PORT=5432
DB_POSTGRESDB_DATABASE=n8n
DB_POSTGRESDB_USER=n8n
DB_POSTGRESDB_PASSWORD=your_password
```

**n8n dengan MySQL (Production):**
```bash
DB_TYPE=mysqldb
DB_MYSQLDB_HOST=localhost
DB_MYSQLDB_PORT=3306
DB_MYSQLDB_DATABASE=n8n
DB_MYSQLDB_USER=n8n
DB_MYSQLDB_PASSWORD=your_password
```

## 🚀 Setup Backend Laravel

### 1. Install Dependencies
```bash
cd backend
composer install
```

### 2. Setup Environment
```bash
# Copy .env.example ke .env
cp .env.example .env

# Generate app key
php artisan key:generate
```

### 3. Konfigurasi Database di .env
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wa_finance
DB_USERNAME=root
DB_PASSWORD=your_password

# Timezone
APP_TIMEZONE=Asia/Makassar

# Internal API Key untuk n8n
INTERNAL_API_KEY=your-secret-api-key-here-change-this
```

### 4. Buat Database
```sql
CREATE DATABASE wa_finance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Run Migrations
```bash
php artisan migrate
```

### 6. Test Backend
```bash
# Start server
php artisan serve

# Test endpoint (dari terminal lain)
curl http://localhost:8000
```

## 🔧 Setup n8n

### Opsi 1: n8n dengan SQLite (Development)
```bash
# Install n8n global
npm install -g n8n

# Run n8n
n8n start
```

n8n akan otomatis pakai SQLite, tidak perlu setup database.

### Opsi 2: n8n dengan Docker (Recommended)
```bash
# Docker run dengan SQLite (default)
docker run -it --rm \
  --name n8n \
  -p 5678:5678 \
  -v ~/.n8n:/home/node/.n8n \
  n8nio/n8n

# Atau dengan PostgreSQL
docker run -it --rm \
  --name n8n \
  -p 5678:5678 \
  -e DB_TYPE=postgresdb \
  -e DB_POSTGRESDB_HOST=postgres \
  -e DB_POSTGRESDB_DATABASE=n8n \
  -e DB_POSTGRESDB_USER=n8n \
  -e DB_POSTGRESDB_PASSWORD=password \
  -v ~/.n8n:/home/node/.n8n \
  n8nio/n8n
```

### Opsi 3: n8n Cloud
- Daftar di https://n8n.io
- Langsung pakai, tidak perlu setup database

## 📥 Import n8n Workflows

1. Buka n8n UI: `http://localhost:5678`
2. Klik **Workflows** > **Import from File**
3. Import file:
   - `n8n-workflows/01-chat-reminder-control.json`
   - `n8n-workflows/02-daily-reminder-cron.json`

## ⚙️ Konfigurasi n8n Environment Variables

Di n8n, setup environment variables:

1. Buka n8n Settings > **Environment Variables**
2. Tambahkan:
   ```
   BACKEND_URL=http://localhost:8000
   INTERNAL_API_KEY=your-secret-api-key-here (sama dengan di backend .env)
   GOWA_URL=http://localhost:3000 (atau URL GoWA kamu)
   AI_API_URL=https://openrouter.ai/api/v1/chat/completions
   OPENROUTER_API_KEY=sk-or-v1-... (API key dari OpenRouter)
   ```

Atau jika pakai Docker, tambahkan di docker-compose:
```yaml
environment:
  - BACKEND_URL=http://host.docker.internal:8000
  - INTERNAL_API_KEY=your-secret-api-key
  - GOWA_URL=http://host.docker.internal:3000
```

## 🔗 Setup GoWA Webhook

1. Setelah import workflow "Chat & Reminder Control", copy webhook URL
   - Contoh: `http://localhost:5678/webhook/wa-in`

2. Konfigurasi GoWA untuk mengirim webhook ke URL tersebut:
   - Buka GoWA admin panel
   - Settings > Webhooks
   - Set URL: `http://localhost:5678/webhook/wa-in`
   - Method: POST

## ✅ Testing

### Test Backend API
```bash
# Test check-or-create user
curl -X POST http://localhost:8000/api/internal/users/check-or-create \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: your-secret-api-key" \
  -d '{"phone_number": "6281234567890", "name": "Test User"}'

# Test get reminder candidates
curl -X GET http://localhost:8000/api/internal/reminders/today-empty \
  -H "X-API-KEY: your-secret-api-key"
```

### Test n8n Webhook
```bash
# Simulate incoming WhatsApp message
curl -X POST http://localhost:5678/webhook/wa-in \
  -H "Content-Type: application/json" \
  -d '{"from": "6281234567890", "text": "jangan ingatkan"}'
```

### Test GoWA Send Message
```bash
# Test send message via GoWA
curl -X POST http://localhost:3000/api/send \
  -H "Content-Type: application/json" \
  -d '{"to": "6281234567890", "message": "Test message"}'
```

## 🐳 Docker Setup (Opsional)

Jika mau pakai Docker untuk semua:

### Backend dengan Docker
```bash
cd backend
docker-compose up -d
```

### n8n dengan Docker Compose
Buat `docker-compose.n8n.yml`:
```yaml
version: '3.8'
services:
  n8n:
    image: n8nio/n8n
    ports:
      - "5678:5678"
    environment:
      - DB_TYPE=postgresdb
      - DB_POSTGRESDB_HOST=postgres
      - DB_POSTGRESDB_DATABASE=n8n
      - DB_POSTGRESDB_USER=n8n
      - DB_POSTGRESDB_PASSWORD=password
      - BACKEND_URL=http://backend:8000
      - INTERNAL_API_KEY=your-secret-api-key
      - GOWA_URL=http://gowa:3000
    volumes:
      - ~/.n8n:/home/node/.n8n
    depends_on:
      - postgres

  postgres:
    image: postgres:15
    environment:
      POSTGRES_DB: n8n
      POSTGRES_USER: n8n
      POSTGRES_PASSWORD: password
    volumes:
      - n8n_data:/var/lib/postgresql/data

volumes:
  n8n_data:
```

## 📝 Checklist Setup

- [ ] Install PHP dan extensions
- [ ] Install Composer
- [ ] Setup MySQL/PostgreSQL database
- [ ] Install n8n (SQLite untuk dev, PostgreSQL untuk prod)
- [ ] Setup backend Laravel (.env, migrations)
- [ ] Import n8n workflows
- [ ] Setup n8n environment variables
- [ ] Konfigurasi GoWA webhook
- [ ] Test semua endpoints
- [ ] Test end-to-end flow

## 🔍 Troubleshooting

### Backend tidak bisa connect ke database
- Pastikan MySQL/PostgreSQL running
- Check credentials di `.env`
- Pastikan database sudah dibuat

### n8n tidak bisa connect ke backend
- Check `BACKEND_URL` di n8n environment variables
- Pastikan backend running dan accessible
- Check firewall/network settings

### GoWA tidak terima webhook
- Pastikan n8n webhook URL benar
- Check n8n workflow sudah active
- Test webhook dengan curl

## 📚 Next Steps

Setelah semua setup:
1. Test chat flow end-to-end
2. Test reminder cron (atau trigger manual)
3. Monitor logs untuk error
4. Setup production environment


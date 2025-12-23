# WA Finance Bot

WhatsApp Finance Bot untuk mencatat transaksi keuangan via WhatsApp dengan integrasi GoWA, n8n, dan Backend Laravel.

## 🎯 Fitur Phase 1

- ✅ Auto-register user berdasarkan nomor WhatsApp
- ✅ Kontrol reminder ON/OFF via chat
- ✅ Reminder harian untuk user yang belum input transaksi
- ✅ API internal untuk n8n (server-to-server)
- ✅ Multi-user isolation dengan phone_number sebagai identifier

## 📁 Struktur Proyek

```
saasuang/
├── backend/              # Backend Laravel API
│   ├── app/
│   ├── database/
│   ├── routes/
│   └── ...
├── n8n-workflows/        # n8n workflow JSON files
│   ├── 01-chat-reminder-control.json
│   ├── 02-daily-reminder-cron.json
│   └── README.md
├── docs/                 # Dokumentasi
│   └── GOWA_CONTRACT.md
└── wa-finance-bot-docs/  # Dokumentasi asli
```

## 🚀 Quick Start

### Prerequisites

- PHP >= 8.1
- Composer
- MySQL/PostgreSQL
- Docker & Docker Compose (recommended)
- n8n (self-hosted atau cloud)
- GoWA (WhatsApp gateway)

### 1. Setup Backend

```bash
cd backend

# Copy environment file
cp .env.example .env

# Install dependencies
composer install

# Generate app key
php artisan key:generate

# Setup database di .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=wa_finance
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Set API Key untuk n8n
# INTERNAL_API_KEY=your-secret-api-key-here

# Run migrations
php artisan migrate

# Start dengan Docker
docker-compose up -d

# Atau start manual
php artisan serve
```

Backend akan berjalan di `http://localhost:8000`

### 2. Setup n8n Workflows

1. Buka n8n UI (biasanya `http://localhost:5678`)
2. Import workflows dari folder `n8n-workflows/`:
   - `01-chat-reminder-control.json`
   - `02-daily-reminder-cron.json`
3. Setup Environment Variables di n8n:
   - `BACKEND_URL`: `http://localhost:8000` (atau URL backend kamu)
   - `INTERNAL_API_KEY`: sama dengan yang di `.env` backend
   - `GOWA_URL`: URL GoWA API kamu
4. Copy webhook URL dari workflow "Chat & Reminder Control"
5. Konfigurasi GoWA untuk mengirim webhook ke URL tersebut

### 3. Setup GoWA

1. Install dan setup GoWA sesuai dokumentasi GoWA
2. Konfigurasi webhook di GoWA:
   - URL: webhook URL dari n8n (contoh: `https://n8n.example.com/webhook/wa-in`)
   - Method: POST
3. Pastikan GoWA menyediakan endpoint `/api/send` untuk mengirim pesan

Lihat `docs/GOWA_CONTRACT.md` untuk detail kontrak integrasi.

## 📡 API Endpoints

Semua endpoint internal memerlukan header `X-API-KEY`.

### POST `/api/internal/users/check-or-create`
Check atau create user berdasarkan phone_number. Mengembalikan `response_style`.

**Request:**
```json
{
  "phone_number": "62812xxxx",
  "name": "optional"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "phone_number": "62812xxxx",
    "name": null,
    "plan": "free",
    "status": "active",
    "reminder_enabled": true,
    "is_unlimited": false,
    "response_style": "santai"
  }
}
```

### POST `/api/internal/users/reminder`
Update reminder_enabled flag untuk user.

**Request:**
```json
{
  "phone_number": "62812xxxx",
  "reminder_enabled": false
}
```

### POST `/api/internal/users/style`
Set gaya balasan user: `santai`, `netral` (alias `biasa`), `formal`, `gaul`.

**Request:**
```json
{
  "phone_number": "62812xxxx",
  "style": "gaul"
}
```

### GET `/api/internal/reminders/today-empty`
Get list user yang belum input transaksi hari ini dan reminder_enabled=true.

**Response:**
```json
{
  "success": true,
  "date": "2024-01-01",
  "items": [
    {
      "phone_number": "62812xxxx"
    }
  ]
}
```

## 💬 Chat Commands

User bisa mengontrol reminder via chat:

- **Matikan reminder**: "jangan ingat", "stop reminder", "matikan reminder", dll
- **Nyalakan reminder**: "nyalakan reminder", "aktifkan reminder", "reminder on"
- **Info reminder**: "reminder apa", "kenapa diingatkan", "cara matiin reminder"
- **Ganti gaya balas**: "gaya santai", "gaya biasa", "gaya netral", "gaya formal", "gaya gaul"

## 🔒 Security

- API Key authentication untuk semua internal endpoints
- Rate limiting per phone_number (opsional, bisa ditambahkan)
- Input validation dan sanitization
- Multi-user isolation: backend resolve user by phone_number, tidak menerima user_id dari client

## 📚 Dokumentasi

- [Backend README](backend/README.md)
- [n8n Workflows README](n8n-workflows/README.md)
- [GoWA Contract](docs/GOWA_CONTRACT.md)
- [Dokumentasi Asli](wa-finance-bot-docs/README.MD)

## 🐳 Docker Deployment

Backend sudah include Docker Compose setup:

```bash
cd backend
docker-compose up -d
```

Ini akan start:
- Laravel app (PHP-FPM)
- Nginx
- MySQL

## 🔧 Development

### Database Schema

- `users`: User data dengan phone_number sebagai unique identifier
- `transactions`: Transaksi keuangan (disiapkan untuk next step)

Lihat `backend/database/migrations/` untuk detail schema.

### Timezone

Default timezone: `Asia/Makassar`

## 📝 Phase 1 Coverage

- ✅ Auto-register user via phone_number
- ✅ Reminder ON/OFF via chat
- ✅ Reminder harian
- ✅ Gaya balasan per user (santai/netral/formal/gaul) dengan hybrid reply (AI hanya untuk gaul, fallback santai)
- ✅ Endpoint batch transaksi & summary hari ini (basis)

## 🤝 Contributing

Ikuti dokumentasi di `wa-finance-bot-docs/` untuk guidelines implementasi.

## 📄 License

MIT

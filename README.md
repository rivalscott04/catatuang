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
   - `AI_API_URL`: `https://openrouter.ai/api/v1/chat/completions` (untuk rewrite gaya gaul)
   - `OPENROUTER_API_KEY`: API key dari OpenRouter (dapatkan di https://openrouter.ai/keys)
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
    "phone_number": "62812xxxx",
    "response_style": "santai",
    "reminder_enabled": true,
    "status": "active",
    "plan": "pro",
    "limits": {
      "chat": {
        "limit": 200,
        "used": 50,
        "remaining": 150
      },
      "struk": {
        "limit": 50,
        "used": 10,
        "remaining": 40
      }
    }
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

### POST `/api/internal/users/increment-chat`
Increment chat count untuk user. Dipanggil oleh n8n setiap ada pesan masuk. Return 429 jika limit exceeded.

**Request:**
```json
{
  "phone_number": "62812xxxx"
}
```

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "phone_number": "62812xxxx",
    "plan": "pro",
    "limits": {
      "chat": {
        "limit": 200,
        "used": 150,
        "remaining": 50
      },
      "struk": {
        "limit": 50,
        "used": 10,
        "remaining": 40
      }
    }
  }
}
```

**Response (Limit Exceeded - 429):**
```json
{
  "success": false,
  "message": "Chat limit exceeded",
  "data": {
    "phone_number": "62812xxxx",
    "plan": "pro",
    "limit_exceeded": true,
    "limits": {
      "chat": {
        "allowed": false,
        "remaining": 0,
        "limit": 200,
        "used": 200
      }
    }
  }
}
```

### GET `/api/internal/users/limits`
Get current limit status untuk user.

**Request Query:**
```
?phone_number=62812xxxx
```

**Response:**
```json
{
  "success": true,
  "data": {
    "phone_number": "62812xxxx",
    "plan": "vip",
    "limits": {
      "chat": {
        "limit": null,
        "used": 500,
        "remaining": null
      },
      "struk": {
        "limit": 200,
        "used": 50,
        "remaining": 150
      }
    }
  }
}
```

### GET `/api/internal/subscriptions/expiring-soon`
Get list user yang subscription-nya akan expired dalam X hari (default: 2 hari). Digunakan untuk reminder.

**Request Query:**
```
?days=2
```

**Response:**
```json
{
  "success": true,
  "data": {
    "days": 2,
    "target_date": "2024-01-18",
    "count": 5,
    "items": [
      {
        "phone_number": "62812xxxx",
        "plan": "pro",
        "subscription_expires_at": "2024-01-18",
        "days_until_expiry": 2,
        "response_style": "santai"
      }
    ]
  }
}
```

### GET `/api/internal/subscriptions/expired`
Get list user yang subscription-nya sudah expired.

**Response:**
```json
{
  "success": true,
  "data": {
    "count": 3,
    "items": [
      {
        "phone_number": "62812xxxx",
        "plan": "pro",
        "subscription_expires_at": "2024-01-15"
      }
    ]
  }
}
```

### POST `/api/internal/subscriptions/mark-expired`
Mark subscriptions sebagai expired (dipanggil oleh cron atau manual).

**Response:**
```json
{
  "success": true,
  "data": {
    "updated_count": 3,
    "message": "Marked 3 subscriptions as expired"
  }
}
```

## 💬 Chat Commands

User bisa mengontrol reminder via chat:

- **Matikan reminder**: "jangan ingat", "stop reminder", "matikan reminder", dll
- **Nyalakan reminder**: "nyalakan reminder", "aktifkan reminder", "reminder on"
- **Info reminder**: "reminder apa", "kenapa diingatkan", "cara matiin reminder"
- **Ganti gaya balas**: "gaya santai", "gaya biasa", "gaya netral", "gaya formal", "gaya gaul"

## 📊 Limit System

Sistem limit bulanan untuk chat text dan struk berdasarkan plan:

- **Free**: 
  - Chat text: 10/bulan
  - Struk: 1/bulan
- **Pro**: 
  - Chat text: 200/bulan
  - Struk: 50/bulan
- **VIP**: 
  - Chat text: Unlimited
  - Struk: 200/bulan

Limit di-reset otomatis setiap bulan. Counter di-increment otomatis:
- Chat: setiap pesan masuk (via `POST /api/internal/users/increment-chat`)
- Struk: setiap transaksi dengan `source='receipt'` (via `POST /api/internal/transactions/batch`)

Jika limit exceeded, API akan return HTTP 429 dengan detail limit.

## 🔒 Security

- API Key authentication untuk semua internal endpoints
- Rate limiting per phone_number (opsional, bisa ditambahkan)
- Input validation dan sanitization
- Multi-user isolation: backend resolve user by phone_number, tidak menerima user_id dari client

## 📅 Subscription Management

Sistem subscription dengan reminder otomatis:

- **Free Plan**: Trial 3 hari, auto-expired setelah itu
- **Pro/VIP Plan**: Subscription 30 hari
- **Auto Reminder**: n8n mengirim reminder 2 hari sebelum expired
- **Status Tracking**: `active`, `expired`, `cancelled`

Workflow n8n `04-subscription-expiry-reminder.json` berjalan setiap hari jam 08:00 untuk mengirim reminder.

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

# Security & Ops

## 1) API Key (n8n -> backend)
- Header: `X-API-KEY`
- Backend wajib reject request tanpa API key
- Simpan secret di env:
  - n8n: environment variable / credential
  - backend: `.env`

## 2) Rate Limit
Untuk mencegah abuse:
- rate limit per `phone_number` (misalnya 60 req/menit)
- opsional gunakan Redis saat traffic meningkat

## 3) Logging
- Simpan log minimal: endpoint, phone_number, status code, latency
- Jangan simpan isi chat mentah terlalu lama (privacy)

## 4) Deployment (Docker)
Minimal 1 VPS:
- GoWA container
- n8n container
- backend container
- db container

Atur jaringan:
- expose publik: n8n webhook (dan GoWA jika diperlukan)
- backend bisa private internal network (lebih aman)

## 5) Failure Handling
- GoWA down: n8n retry send message (backoff)
- Backend down: n8n balas fallback + log error
- DB down: backend return 500 + n8n notify admin (optional)

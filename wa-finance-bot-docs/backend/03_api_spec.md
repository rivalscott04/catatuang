
---

# FILE: `wa-finance-bot-docs/backend/03_api_spec.md`

```md
# API Spec (Backend)

Semua endpoint di bawah ini dipakai oleh n8n (server-to-server).
Proteksi:
- Header wajib: `X-API-KEY: <secret>`

## Base Rules
- `Content-Type: application/json`
- Timezone konsisten (disarankan `Asia/Makassar`)

---

## 1) Check-or-Create User

### POST `/internal/users/check-or-create`
Dipanggil tiap pesan masuk agar user terdaftar otomatis.

Request:
```json
{
  "phone_number": "62812xxxx",
  "name": "optional"
}

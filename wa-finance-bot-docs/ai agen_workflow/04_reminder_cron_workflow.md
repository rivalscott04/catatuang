# n8n Workflow: Cron Reminder Harian

Tujuan:
- setiap hari jam tertentu, kirim reminder ke user yang belum input transaksi hari ini
- hanya user yang `reminder_enabled=true`

## Node List (urutan detail)
1) Cron
- Every day
- Time: 20:00
- Timezone: Asia/Makassar (atau konsisten dengan backend)

2) HTTP Request -> Backend
- GET `/internal/reminders/today-empty`
- Header: `X-API-KEY`

Response contoh:
```json
{
  "date": "2025-12-23",
  "items": [{"phone_number":"62812xxx"}]
}

# n8n Workflow Step 1: Chat Dasar + Kontrol Reminder

Tujuan workflow:
- menerima pesan WhatsApp dari GoWA
- check-or-create user di backend
- deteksi intent: reminder_off / reminder_on / ask_reminder / normal
- update reminder flag via backend untuk ON/OFF
- kirim balasan via GoWA dengan template random

## Input dari GoWA
Payload minimal:
```json
{
  "from": "62812xxxx",
  "text": "jangan ingatkan"
}

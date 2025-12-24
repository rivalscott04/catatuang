# n8n Workflows untuk WA Finance Bot

Workflow n8n untuk menangani chat WhatsApp dan reminder harian.

## Setup

1. Import workflow JSON ke n8n:
   - Buka n8n UI
   - Klik "Workflows" > "Import from File"
   - Pilih file JSON workflow

2. Setup Environment Variables di n8n:
   - `BACKEND_URL`: URL backend API (contoh: `http://localhost:8000`)
   - `INTERNAL_API_KEY`: API key untuk autentikasi ke backend
   - `GOWA_URL`: URL GoWA API (contoh: `http://localhost:3000`)
   - `AI_API_URL`: URL OpenRouter API (contoh: `https://openrouter.ai/api/v1/chat/completions`)
   - `OPENROUTER_API_KEY`: API key dari OpenRouter (format: `sk-or-v1-...`)

3. Setup Webhook URL:
   - Setelah import workflow "Chat & Reminder Control", copy webhook URL
   - Konfigurasi GoWA untuk mengirim webhook ke URL tersebut

## Workflows

### 1. Chat & Reminder Control (`01-chat-reminder-control.json`)

Workflow untuk menangani pesan masuk dari WhatsApp:
- Normalize payload dari GoWA
- Check atau create user di backend
- Detect intent (reminder_off, reminder_on, ask_reminder, set_style, normal)
- Update reminder flag jika perlu
- Set response style jika menerima perintah:
  - "gaya santai" -> santai
  - "gaya biasa" -> netral
  - "gaya netral" -> netral
  - "gaya formal" -> formal
  - "gaya gaul" -> gaul
- Fetch response_style terkini untuk setiap chat sebelum membalas
- Template balasan menyesuaikan response_style
- Generate reply dengan template random
- Kirim balasan via GoWA dengan delay random

**Webhook Path**: `/webhook/wa-in`

**Intent Detection**:
- `reminder_off`: "jangan ingat", "stop reminder", dll
- `reminder_on`: "nyalakan reminder", "reminder on", dll
- `ask_reminder`: "reminder apa", "kenapa diingatkan", dll
- `normal`: default untuk pesan lainnya

### 2. Daily Reminder Cron (`02-daily-reminder-cron.json`)

Workflow untuk mengirim reminder harian:
- Trigger setiap hari jam 20:00 (Asia/Makassar)
- Fetch user yang belum input transaksi hari ini
- Loop per user (batch=1)
- Generate reminder message dengan template random
- Kirim via GoWA dengan delay random dan retry

### 3. Hybrid Reply Style (`03-hybrid-reply-style.json`)

Workflow untuk gaya balas hybrid (template + optional AI rewrite jika style=gaul):
- Webhook `wa-hybrid`
- Normalize payload
- Check/Create user & ambil response_style
- Bangun pesan template berdasarkan style (santai/netral/formal/gaul)
- Jika style=gaul → panggil LLM untuk rewrite tone (prompt singkat) dengan fallback ke template santai bila gagal/timeout
- Tambah delay random 1–3 detik
- Kirim via GoWA dengan retry backoff 2s, 5s, 10s

## Function Node Snippets

### Normalize Payload
```javascript
const phone = ($json.from || $json.phone || "").toString().trim();
const text = ($json.text || $json.message || "").toString();

return [{ json: { phone, text } }];
```

### Detect Intent
```javascript
const text = ($json.text || "").toLowerCase();

const offKeywords = ['jangan ingat', 'jangan diingatkan', 'stop reminder', 'matikan reminder', 'jangan reminder'];
const onKeywords = ['nyalakan reminder', 'aktifkan reminder', 'reminder on'];
const askKeywords = ['kok diingetin', 'kenapa diingatkan', 'reminder apa', 'cara matiin reminder', 'gimana stop'];

let intent = 'normal';
if (offKeywords.some(kw => text.includes(kw))) {
  intent = 'reminder_off';
} else if (onKeywords.some(kw => text.includes(kw))) {
  intent = 'reminder_on';
} else if (askKeywords.some(kw => text.includes(kw))) {
  intent = 'ask_reminder';
}

return [{ json: { ...$json, intent } }];
```

## Testing

1. Test webhook dengan curl:
```bash
curl -X POST http://localhost:5678/webhook/wa-in \
  -H "Content-Type: application/json" \
  -d '{"from": "62812xxxx", "text": "jangan ingat"}'
```

2. Test cron dengan manual trigger di n8n UI

## Troubleshooting

- Pastikan environment variables sudah di-set di n8n
- Pastikan backend API accessible dari n8n
- Pastikan GoWA API accessible dari n8n
- Check n8n execution logs untuk error details


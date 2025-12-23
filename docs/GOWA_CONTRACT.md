# GoWA Contract (Minimal Integration)

Dokumen ini mendefinisikan kontrak payload minimum antara GoWA dan n8n untuk WA Finance Bot.

## 1) Incoming Message: GoWA -> n8n

GoWA mengirim POST request ke n8n webhook ketika menerima pesan WhatsApp.

### Endpoint
```
POST https://N8N_HOST/webhook/wa-in
Content-Type: application/json
```

### Payload Format
```json
{
  "from": "62812xxxx",
  "text": "jangan ingatkan",
  "message_id": "optional_message_id",
  "timestamp": "optional_timestamp"
}
```

### Field Description
- `from` (required): Nomor pengirim dalam format E.164 tanpa plus (contoh: `62812xxxx`)
- `text` (required): Isi pesan teks dari pengirim
- `message_id` (optional): ID unik pesan dari GoWA
- `timestamp` (optional): Timestamp pesan

### Example Request
```bash
curl -X POST https://n8n.example.com/webhook/wa-in \
  -H "Content-Type: application/json" \
  -d '{
    "from": "6281234567890",
    "text": "jangan ingatkan",
    "message_id": "msg_123456",
    "timestamp": "2024-01-01T20:00:00Z"
  }'
```

## 2) Outgoing Message: n8n -> GoWA

n8n mengirim POST request ke GoWA untuk mengirim pesan WhatsApp.

### Endpoint
```
POST https://GOWA_HOST/api/send
Content-Type: application/json
```

### Payload Format
```json
{
  "to": "62812xxxx",
  "message": "Halo! Saya bot pencatat keuangan.",
  "delay": 2000
}
```

### Field Description
- `to` (required): Nomor tujuan dalam format E.164 tanpa plus (contoh: `62812xxxx`)
- `message` (required): Isi pesan yang akan dikirim
- `delay` (optional): Delay dalam milliseconds sebelum mengirim (untuk anti-spam)

### Response Format
```json
{
  "success": true,
  "message_id": "msg_789012",
  "status": "sent"
}
```

### Error Response
```json
{
  "success": false,
  "error": "Invalid phone number",
  "code": "INVALID_PHONE"
}
```

### Example Request
```bash
curl -X POST https://gowa.example.com/api/send \
  -H "Content-Type: application/json" \
  -d '{
    "to": "6281234567890",
    "message": "Reminder: Jangan lupa catat transaksi hari ini!",
    "delay": 2000
  }'
```

## 3) Error Handling

### Retry Strategy
- Workflow Hybrid: retry 3x dengan delay 2s, 5s, 10s untuk HTTP >= 500, timeout, atau connection refused.
- Workflow lainnya: tetap gunakan retry bawaan n8n bila diaktifkan.
- Jika LLM (rewrite gaya gaul) gagal/timeout → fallback ke template santai (tanpa AI) agar fakta tidak berubah.

## 4) Rate Limiting

Untuk mencegah abuse:
- GoWA harus implement rate limiting per phone number
- Recommended: 60 requests per minute per phone number
- Return HTTP 429 jika limit exceeded

## 5) Security

### Authentication (Optional)
Jika GoWA memerlukan authentication:
```http
Authorization: Bearer YOUR_GOWA_API_TOKEN
```

### Webhook Security
- n8n webhook harus menggunakan HTTPS di production
- Consider menggunakan webhook secret untuk validasi

## 6) Testing

### Test Incoming Webhook
```bash
# Simulate incoming message
curl -X POST http://localhost:5678/webhook/wa-in \
  -H "Content-Type: application/json" \
  -d '{
    "from": "6281234567890",
    "text": "test message"
  }'
```

### Test Outgoing Message
```bash
# Send message via GoWA
curl -X POST http://localhost:3000/api/send \
  -H "Content-Type: application/json" \
  -d '{
    "to": "6281234567890",
    "message": "Test message from n8n"
  }'
```

## 7) GoWA Configuration

### Webhook Setup di GoWA
1. Buka GoWA admin panel
2. Navigate ke Settings > Webhooks
3. Set webhook URL: `https://N8N_HOST/webhook/wa-in`
4. Set method: POST
5. Enable webhook

### GoWA API Endpoint
Pastikan GoWA menyediakan endpoint `/api/send` untuk mengirim pesan.

## Notes

- Format phone number harus konsisten: E.164 tanpa plus (numeric only)
- Timezone default: Asia/Makassar
- Semua timestamp dalam format ISO 8601
- Message length limit: 4096 characters (WhatsApp limit)
- Untuk setiap pesan inbound, n8n akan fetch profil user (termasuk `response_style`) dari backend sebelum mengirim balasan.



---

# FILE: `wa-finance-bot-docs/backend/04_business_rules.md`

```md
# Business Rules

Dokumen ini berisi aturan operasional untuk Step 1 + Reminder, sekaligus pondasi untuk scaling.

## 1) Reminder Rules
- Default `reminder_enabled=true` untuk user baru
- Reminder hanya dikirim:
  - 1 kali per hari pada jam yang ditentukan (via n8n cron)
  - user `status=active`
  - `reminder_enabled=true`
  - belum ada transaksi hari ini
- User bisa matikan/nyalakan lewat chat (intent detection di n8n)

## 2) Intent Rules (Chat)
Intent OFF (mematikan reminder) jika chat mengandung salah satu:
- `jangan ingat`
- `jangan diingatkan`
- `stop reminder`
- `matikan reminder`
- `jangan reminder`

Intent ON (menyalakan reminder) jika chat mengandung:
- `nyalakan reminder`
- `aktifkan reminder`
- `reminder on`

Intent ASK (penjelasan) jika chat mengandung:
- `kok diingetin`
- `kenapa diingatkan`
- `reminder apa`
- `cara matiin reminder`
- `gimana stop`

Jika tidak cocok → intent `normal`

## 3) Response Variation (Anti monoton + safety)
- Reply selalu pakai template pool (random)
- Tambahkan delay random 1–3 detik sebelum send message
- Hindari pesan yang identik terus menerus

## 4) Unlimited / Whitelist (opsional)
Jika ada nomor tertentu unlimited:
- set `users.is_unlimited=true`
- untuk fitur quota (next step) unlimited bypass quota

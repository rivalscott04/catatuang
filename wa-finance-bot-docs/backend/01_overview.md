# Backend Overview

Backend adalah pusat logika bisnis dan pemisah data antar user. n8n hanya routing + automasi, sedangkan backend memutuskan:
- user auto-register berdasarkan nomor WhatsApp
- reminder on/off
- simpan transaksi
- rekap harian (masuk, keluar, saldo)
- query user yang belum input transaksi untuk reminder

## Komponen
- API Service: Laravel atau Node (Laravel preferred)
- DB: MySQL/Postgres
- Auth internal: API Key untuk n8n (server-to-server)

## Konsep Multi User
Identity utama = `phone_number`. Semua transaksi punya `user_id` dari tabel `users`.
Semua query transaksi wajib filter `user_id`.

## Alur Data (Step 1 + Reminder)
1) WhatsApp (GoWA) mengirim payload ke n8n webhook
2) n8n deteksi intent: reminder_off / reminder_on / ask_reminder / normal
3) n8n call backend untuk check-or-create user, dan update reminder flag bila perlu
4) n8n balas WhatsApp via GoWA
5) Cron harian (n8n) memanggil backend: list user yang hari ini belum input transaksi dan reminder_enabled=true
6) n8n loop kirim reminder ke user tersebut

## Konvensi Implementasi
- Semua response API berbentuk JSON
- Semua endpoint internal diproteksi dengan header `X-API-KEY`
- Backend tidak menerima `user_id` dari client. Backend resolve user by phone_number.

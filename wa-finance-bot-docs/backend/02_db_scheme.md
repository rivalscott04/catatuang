# Database Schema

Dokumen ini mendeskripsikan tabel minimal untuk Step 1 + Reminder, sekaligus pondasi agar mudah lanjut ke fitur transaksi, subscription, dsb.

## 1) Tabel `users`

### Kolom
- `id`: uuid/bigint (PK)
- `phone_number`: varchar(20) UNIQUE NOT NULL
- `name`: varchar(120) NULL (opsional)
- `plan`: enum('free','pro','biz') default 'free'
- `status`: enum('active','blocked') default 'active'
- `reminder_enabled`: boolean default true
- `is_unlimited`: boolean default false
- `created_at`, `updated_at`

### Indeks
- UNIQUE: `phone_number`
- INDEX: (`reminder_enabled`, `status`)

### Catatan
- format rekomendasi: numeric E.164 tanpa plus, contoh `62812xxxx`

## 2) Tabel `transactions` (disiapkan untuk next step)

### Kolom
- `id`: bigint/uuid (PK)
- `user_id`: FK -> users.id
- `tanggal`: date NOT NULL
- `amount`: bigint NOT NULL
- `description`: text NOT NULL
- `type`: enum('income','expense') NOT NULL
- `source`: enum('text','receipt') default 'text'
- `created_at`, `updated_at`

### Indeks
- INDEX: (`user_id`, `tanggal`)
- INDEX: (`user_id`, `type`, `tanggal`)

## 3) Query Penting

### A) User yang belum catat transaksi hari ini (untuk reminder)
```sql
SELECT u.phone_number
FROM users u
LEFT JOIN transactions t
  ON t.user_id = u.id
 AND t.tanggal = CURRENT_DATE
WHERE u.status = 'active'
  AND u.reminder_enabled = true
  AND t.id IS NULL;
```
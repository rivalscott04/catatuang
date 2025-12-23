
---

# FILE: `wa-finance-bot-docs/ai_agent_workflow/03_ai_prompts.md`

```md
# AI Prompts (Hemat, JSON-only)

Dokumen ini untuk next step (parsing transaksi & OCR struk). Step 1 tidak membutuhkan LLM.

Model rekomendasi hemat:
- GPT-4o-mini atau GPT-4.1-mini

Prinsip:
- Output JSON saja
- Hindari narasi panjang
- Validasi hasil di backend

## A) Parsing Teks Multi-baris (fallback jika regex gagal)

Prompt:
Kamu adalah sistem pencatat keuangan.
Pecah teks menjadi transaksi per baris.
Aturan:
- 'k' = ribuan (20k = 20000)
- type = income jika mengandung: gaji, bonus, masuk, transfer masuk
- type = expense jika mengandung: bayar, belanja, bensin, listrik, air
Jawab JSON saja tanpa penjelasan.

Format:
[
  {"amount": number, "description": string, "type": "income|expense"}
]

Teks:
{{USER_TEXT}}

## B) OCR Struk (Vision)

Prompt:
Ekstrak dari struk:
- merchant
- tanggal (YYYY-MM-DD jika ada)
- total pembayaran (angka)
- type = expense
Jawab JSON saja.

Format:
{"merchant":"","date":"","amount":number,"type":"expense"}

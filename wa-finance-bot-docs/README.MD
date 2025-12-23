# WA Finance Bot Docs Pack

Paket dokumentasi ini disiapkan untuk AI implementation agent agar dapat menghasilkan code dan workflow secara lengkap.

## Isi
- `backend/` : DB schema, API spec, business rules, security/ops
- `ai_agent_workflow/` : workflow n8n Step 1 + cron reminder, function node snippets, LLM prompts (next step), kontrak GoWA, agent prompt profesional

## Fokus Tahap Ini
- Step 1: Chat dasar + kontrol reminder ON/OFF via WhatsApp
- Reminder harian: cron n8n -> backend query -> kirim WA

## Cara Pakai ke AI Agent
1) Berikan folder ini sebagai konteks
2) Ikuti instruksi pada `ai_agent_workflow/00_agent_prompt.md`

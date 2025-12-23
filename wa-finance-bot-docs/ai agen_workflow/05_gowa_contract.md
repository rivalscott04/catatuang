
---

# FILE: `wa-finance-bot-docs/ai_agent_workflow/05_gowa_contract.md`

```md
# GoWA Contract (Minimal Integration)

Dokumen ini mendefinisikan kontrak payload minimum antara GoWA dan n8n.

## 1) Incoming Message -> n8n
GoWA mengirim POST ke n8n webhook:

```http
POST https://N8N_HOST/webhook/wa-in
Content-Type: application/json

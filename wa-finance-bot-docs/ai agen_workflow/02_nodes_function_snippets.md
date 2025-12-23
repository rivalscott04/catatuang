
---

# FILE: `wa-finance-bot-docs/ai_agent_workflow/02_nodes_function_snippets.md`

```md
# Function Node Snippets (Copy-Paste)

Semua snippet di bawah bisa ditempel langsung ke node `Function` di n8n.

## A) Normalize Payload
Input: `$json` dari webhook  
Output: `{ phone, text }`

```js
const phone = ($json.from || $json.phone || "").toString().trim();
const text = ($json.text || $json.message || "").toString();

return [{ phone, text }];

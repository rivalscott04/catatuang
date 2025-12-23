# Implementation Agent Prompt (Professional)

You are an implementation agent. Generate production-ready code and configuration for a self-hosted WhatsApp finance logging bot using GoWA, n8n, and a Backend API.

## Scope
Phase 1 must deliver:
1) Backend API (Laravel preferred)
2) n8n workflows:
   - Step 1: Chat inbound webhook + reminder control via chat (ON/OFF + help)
   - Daily Cron Reminder: send reminder to users who have not created any transaction today
3) Minimal GoWA integration contract for inbound/outbound messages

## Deliverables
### A) Backend (Laravel preferred)
- Database migrations:
  - `users` table with fields: phone_number (unique), plan, status, reminder_enabled, is_unlimited, timestamps
  - `transactions` table with fields: user_id, tanggal, amount, description, type, source, timestamps
- Middleware:
  - API Key authentication using `X-API-KEY`
- Controllers/Routes for internal endpoints:
  - `POST /internal/users/check-or-create`
  - `POST /internal/users/reminder`
  - `GET  /internal/reminders/today-empty`
  - (prepare) `POST /internal/transactions/batch`
  - (prepare) `GET  /internal/summary/today`
- Validation and consistent JSON error responses
- `.env.example` documenting required environment variables
- Basic README with run instructions (Docker Compose recommended)

### B) n8n workflows
- Provide workflows as importable JSON (preferred) OR node-by-node build instructions
- Workflow 1: Step 1 Chat + Reminder Control
  - Normalize inbound payload
  - Call backend check-or-create
  - Detect intent using keyword matching
  - For reminder_on/off: call backend to update reminder flag
  - Send reply to user via GoWA using template pools and random delay (1-3 seconds)
- Workflow 2: Daily Reminder Cron
  - Cron trigger daily at configured time
  - Call backend to fetch candidates (today-empty)
  - Loop per user (batch=1)
  - Random template message + random delay
  - Send message via GoWA

### C) GoWA contract
- Define inbound payload from GoWA -> n8n
- Define outbound send endpoint from n8n -> GoWA

## Constraints
- Multi-user isolation: backend must resolve user by `phone_number`. Do NOT accept `user_id` from n8n as a trusted input.
- State management: do NOT store business state in n8n. State must live in backend DB.
- Phase 1 must work without any LLM calls.
- Replies must vary (template pools) to avoid repetitive messages.
- Add retries/backoff for sending WA messages.

## Source of Truth
Follow the documentation in this package:
- `backend/*`
- `ai_agent_workflow/*`

Output a complete repository-like structure with code, configs, and instructions.

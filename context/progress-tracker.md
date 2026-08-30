# Progress Tracker

## Current Phase
- Wizard v2 REDESIGN (Guided Builder) — spec tersudah, IMPLEMENTASI P1 BELUM MULAI
- Anggota Fase A & B tetap COMPLETE dan tidak dirombak

## Current Goal
- **P1 (Prioritas 1): Implementasi L1-L3 Guided Builder** sesuai `docs/WIZARD-CHAT-SPEC.md` v2:
  L1 open input (sudah 90%), L2 klarifikasi terpandu (checkbox + custom, max 3 turn), L3 finalisasi skeleton PRD.

## Completed
- [x] Fase A Fondasi + Fase B Transaksi Manual (tiers 4, roles 3, billing/topup/approve/invoice/kupon) — 60 routes, test 25/25, build PASS
- [x] Wizard v1 (chat iteratif L1-L3) + AiService multi-provider + prompt admin /admin/wizard-prompts (WizardPrompt + versioning + rollback)
- [x] WIZARD-CHAT-SPEC.md v2 (Guided Builder) — keputusan user: L2 dynamic questions + max turn 3 + JSON Master L4 (Vue Flow + Mermaid) + L6 opsi screens+prompt v0
- [x] Fix sesi 30 Aug 2026: csrf-token meta (raw fetch 419), callOpenCode timeout 180s + connect 15s, enabled_models = 23 live-OK, sanitasi clone URL SPA (script/style), PDF text extraction (smalot/pdfparser), AssistantBubble.vue (structured render)

## In Progress
- None (menunggu mulai P1)

## Next Up — P1: L1-L3 Guided Builder (per spec v2 §6)
1. Schema content v2 per step (L1/L2/L3) + render fallback v1 + config `ai.wizard.max_clarify_turns` (default 3)
2. WizardController mode per step: L1 open_input / L2 guided_choice / L3 finalize; endpoint L2: questions (AI generate) + answers (turn counter + next_available)
3. PromptResolver: var baru `{{answers}}` + schema questions L2; update wizard_prompts DB L1-L3
4. Frontend: QuestionCard.vue (checkbox + custom), tombol "Lanjut ke Langkah 3" logic, L3 draft + field perbaikan
5. Test WizardChatTest v2 + build

## P2 (setelah P1): L4-L5
6. JSON Master SSOT + CRUD node/edge + MermaidConverter; @vue-flow/core 2 tab; chat-edit agent; L5 entity editor + DBML/SQL/mermaid

## P3: L6-L8
7. L6 screens+wireframe+prompt v0; L7 NFR checklist; L8 compiler + export PDF/MD + versioning (project_versions)

## Open Questions
- Lokasi setting max_clarify_turns (config vs UI admin) — putuskan di P1
- Migrasi project v1 (TOYAA): preserve-read (rekomendasi)
- Package canvas: @vue-flow/core (rekomendasi)

## Architecture Decisions
- Laravel 13 + Inertia Vue 3 + Tailwind 4; **DB MySQL (buatprd)** — bukan sqlite
- Wizard: project_sections.content JSON per step; L4-L5 pakai JSON Master nodes/edges (Vue Flow format) + Mermaid converter
- AiService multi-provider (OpenCode base https://opencode.ai/zen/go/v1, connect 15s / timeout 180s, max_tokens 2500, enabled = 23 model)
- Provider default: opencode_go; Utama minimax-m3 / deepseek-v4-flash (pilihan user), Vision deepseek-v4-flash-vision-exp; user suka longcat-2.0 (vision+murah)

## Session Notes
- Credentials seed: superadmin@buatprd.test / admin@buatprd.test / member@buatprd.test — password `password`
- Prompt wizard dikelola user via /admin/wizard-prompts (step 1-8; 1-3 detail, 4-8 generik)
- Spec v2 = source of truth arah: docs/WIZARD-CHAT-SPEC.md; PRD bab 5.2 & 6 untuk konteks
- Kata kunci lanjutan: "lanjut P1 wizard v2"
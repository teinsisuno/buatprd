# Progress Tracker

## Current Phase
- Fase A Fondasi — COMPLETE (29 Aug 2026)
- Fase B Transaksi Manual — COMPLETE (29 Aug 2026)
- Production Ready Structure Locked

## Current Goal
- Fase B COMPLETE: Billing, TopUp, Approve, Invoice, Kupon. Next: Fase C Wizard PRD 8 Langkah.

## Completed
- [x] PRD Production Ready v1.0 (`docs/PRD-BUATPRD-PRODUCTION.md`)
- [x] Audit existing (Welcome + Dashboard mock, build pass)
- [x] Install spatie/laravel-permission 8.3 + activitylog 5.1, publish config & migrations
- [x] Migrasi 9 tabel baru: permission, activity_log, membership_tiers, user_memberships, transactions, projects, project_sections, project_versions, tickets (+ replies)
- [x] Models: User (HasRoles+LogsActivity), MembershipTier, UserMembership, Transaction, Project, ProjectSection, ProjectVersion, Ticket
- [x] Seeder: 4 tiers (default 0 / basic 49k / standart 99k / premium 199k) + 3 roles + 3 demo users (superadmin/admin/member) + memberships
- [x] Middleware: EnsureRole, EnsureTier + alias role/permission di bootstrap/app.php
- [x] HandleInertiaRequests share: auth.user (roles, is_admin), membership (tier, limits, quota, credit), flash
- [x] Auth: RegisteredUserController → auto role member + default membership, redirect ke member.dashboard. Login redirect role-based (admin → admin.dashboard)
- [x] Routes: /admin/* (guard superadmin|admin) + /member/* (verified) + /dashboard legacy redirect. 60 routes, route:list OK
- [x] Layouts: AdminLayout.vue (8 grup menu: Dashboard, Users, Membership, Paket, Transaksi, Kupon) + MemberLayout.vue (quota card, billing, topup, usage)
- [x] Pages: Admin/Dashboard, Users/Index, Memberships/Index, Transactions/Index, ComingSoon + Member/Dashboard, Projects/Index, Projects/Create, Billing/TopUp/Usage/Tickets/Settings placeholders
- [x] Tests: fix AuthenticationTest & RegistrationTest → assert member.dashboard, resilient to missing role/tier. php artisan test 25/25 PASS
- [x] Build: npm run build PASS (1.08s, AdminLayout 15.18kB, MemberLayout 14.99kB)
- [x] Git init + commit main 3efed4d
- [x] Fase B — Migrasi coupons + add_coupon_to_transactions (coupons table, discount_amount, coupon_code)
- [x] Models: Coupon (isValid, discountAmount) + Transaction (coupon, netAmount, proof_original_name)
- [x] Config: config/buatprd.php (payment BCA/Mandiri + topup_options 25k/50k/100k)
- [x] Controllers: Admin/MembershipController (store/update/destroy/reorder + activity log), Admin/TransactionController (index/show/approve/reject/proof + quota logic), Admin/CouponController (CRUD), Member/BillingController (index/checkout/store/invoice + kupon), Member/TopUpController (index/store), InvoiceController (dompdf PDF)
- [x] Invoice: resources/views/invoices/transaction.blade.php + barryvdh/laravel-dompdf 3.1 installed
- [x] Routes: 60 routes — /admin/memberships CRUD, /admin/transactions/{approve,reject,proof,invoice}, /admin/coupons CRUD, /member/billing/checkout/{tier}, /member/topup, /member/transactions/{proof,invoice}
- [x] Frontend: Admin/Memberships/Index (modal CRUD fitur/limits), Admin/Transactions/Index (approve/reject modal + filter status/type), Admin/Transactions/Show, Admin/Coupons/Index, Member/Billing/Index (paket saya + pricing + riwayat), Member/Billing/Checkout (transfer info + coupon + upload), Member/TopUp/Index (opsi + upload + riwayat)
- [x] Layout: AdminLayout tambah Kupon & Diskon di grup Keuangan
- [x] Verifikasi: approve flow (subscription → tier+quota reset, topup → credit 50/120/270, coupon used_count) via tinker 100% OK, migrate:fresh --seed OK, npm run build PASS (1.4s), php artisan test 25/25 PASS, dompdf installed

## In Progress
- None

## Next Up (Fase C — Wizard PRD Inti)
1. CRUD Project + project_sections 8 row per project (auto create)
2. UI Wizard 8 langkah (/member/project/{id}/wizard/{step}) + progress bar
3. AiService terpusat (OpenAI/Gemini via config/buatprd, prompt per langkah di config/ai.php)
4. Tier guard middleware (default tidak bisa export, basic tidak bisa mermaid)
5. Versioning snapshot + export PDF/Markdown

## Open Questions
- None — rate Hybrid lock 0/49k/99k/199k, topup 25k/50k/100k

## Architecture Decisions
- Tetap Laravel 13 + Inertia Vue 3 + Tailwind 4
- Role via spatie/permission, tier via membership_tiers (JSON limits), user_memberships (quota)
- Manual transfer V1, tanpa Midtrans — upload bukti ke storage/app/private/proofs (mimes jpg/png/webp max 2MB)
- Dual layout AdminLayout & MemberLayout, share via HandleInertiaRequests
- 1 user 1 active membership, 1 project 8 sections (unique project_id+step)
- Coupon: code unique, discount_percent, max_uses, expires_at — potongan dihitung saat approve
- Invoice PDF via barryvdh/laravel-dompdf, fallback HTML jika belum install

## Session Notes
- Credentials seed: superadmin@buatprd.test / admin@buatprd.test / member@buatprd.test — password: `password` (member premium demo)
- DB: database.sqlite, migrate:fresh --seed OK. Tiers: 4, Roles: 3, Coupons: 1 (LAUNCH50 50% 100 uses)
- Build & Tests PASS, siap lanjut Fase C tanpa bongkar struktur
- PRD source: docs/PRD-BUATPRD-PRODUCTION.md Bab 5.2 & 6
- Fase B flow verified: member pilih paket -> checkout -> upload -> admin approve -> membership tier+quota updated + coupon count + credit topup

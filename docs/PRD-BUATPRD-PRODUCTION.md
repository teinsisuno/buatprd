# PRD — BuatPRD (Production Ready) v1.0
> Aplikasi Untuk Membuat PRD dengan Bantuan AI — 8 Langkah Terstruktur
> Status: Production Ready Spec | Tanggal: 29 Agustus 2026 | Owner: Tengku

---

## 1. Overview

**BuatPRD** adalah SaaS workspace untuk mengubah ide kasar menjadi dokumen PRD (Product Requirements Document) yang profesional, lengkap, dan siap dieksekusi tim tech/design. 

Target pengguna: Solo Developer, Product Manager, Founder, dan Agency yang butuh PRD cepat tanpa ribet nulis dari nol. Keunggulan utama adalah **Wizard 8 Langkah dengan AI di setiap langkah** (Ideation Partner → Document Compiler).

**Posisi Produk:** Bukan sekadar generator teks, tapi workspace terarah yang memaksa user berpikir terstruktur (Problem → Metrics → Stories → Diagram → DB → NFR → UX → Export).

---

## 2. Goals (Tujuan Bisnis & Produk)

1.  **Time-to-PRD < 15 menit:** User dari ide kosong sampai PRD jadi (export PDF/Markdown) dalam 15 menit dengan bantuan AI.
2.  **Conversion Free → Paid > 12%:** Tier `default` gratis sebagai hook, dorong upgrade ke `standart` (best seller) via batasan kuota yang terasa tapi tidak menyiksa.
3.  **Retensi Bulanan > 40%:** Member premium renew karena fitur kolaborasi & template yang terus bertambah.
4.  **Operasional Manual yang Rapi:** Seluruh pembayaran via transfer manual tetap tercatat rapi (invoice, bukti, approval) tanpa payment gateway di V1 Production.

---

## 3. Roles & Access Model

### 3.1 Role Hierarchy

| Role | Cara Dapat | Hak Utama | Batasan |
| :--- | :--- | :--- | :--- |
| **Superadmin** | Seed manual (1 akun), tidak bisa daftar via form | Full akses: kelola Admin, kelola semua user, semua setting, hapus data apa pun | Tidak ada |
| **Admin** | Diangkat oleh Superadmin via User Management | Kelola Member (CRUD, suspend), kelola Membership/Paket/Transaksi, kelola Template & Tiket | **TIDAK** bisa kelola Superadmin/Admin lain, tidak bisa ubah Setting Sistem (API Key) |
| **Member** | Daftar via `/register` → otomatis `default` | Akses `/member/*` sesuai Tier-nya | Tidak bisa akses `/admin/*` sama sekali |

**Library:** `spatie/laravel-permission` — Role & Permission via middleware `role:superadmin|admin` dan `tier:premium`.

### 3.2 Membership Tier (Hybrid: Subscription + Top Up)

User daftar = `default` (gratis). Upgrade hanya via **Admin Approve** setelah transfer manual. Durasi paket = **30 hari**, setelah expired otomatis turun ke `default` (grace 3 hari).

| Tier | Harga /30 Hari | Limit Project | Limit Generate AI /bulan | Fitur Kunci |
| :--- | :--- | :--- | :--- | :--- |
| **default** | Rp 0 | 2 | 10x | Wizard 8 langkah, tapi **tidak bisa Export PDF**, tidak ada Diagram Mermaid |
| **basic** | Rp 49.000 | 10 | 100x | Export PDF + Markdown |
| **standart** | Rp 99.000 | 30 | 300x | + Diagram Mermaid/DBML + Hapus Watermark + Template Premium |
| **premium** | Rp 199.000 | Unlimited | 1.000x | + Share Project ke Tim + Prioritas Support + Akses API (future) |

**Top Up Kredit Eceran (jika kuota paket habis sebelum 30 hari):**
*   Rp 25.000 = 50 kredit
*   Rp 50.000 = 120 kredit (bonus 20)
*   Rp 100.000 = 270 kredit (bonus 70)
*   1 kredit = 1x Generate AI di langkah mana pun. Kredit tidak hangus saat renew paket (akumulasi).

**Rate Logic:** Biaya AI rata-rata Rp 700-900/generate, harga di atas memberi margin 55-65% + buffer. Admin bisa edit harga/limit kapan saja via `/admin/paket` tanpa deploy.

---

## 4. Tech Stack (Production)

| Layer | Teknologi | Keterangan |
| :--- | :--- | :--- |
| Framework | **Laravel 13 + Inertia.js + Vue 3** | Tetap, Breeze Auth sudah jadi |
| UI | Tailwind 4 + `ui-context.md` (obsidian/violet/champagne) | Reuse token `var(--bg-main)` dll |
| Auth & Role | `spatie/laravel-permission` + `spatie/laravel-activitylog` | Audit log siapa ubah tier/transaksi |
| DB | MySQL (Laragon) + Eloquent |  |
| Build | Vite 8 | `npm run build` wajib pass |
| AI | OpenAI / Gemini via Laravel Http (API Key di Setting) | Service `App\Services\AiService` terpusat |
| Diagram | `mermaid.js` (frontend render) | Langkah 4 |
| Export | `barryvdh/laravel-dompdf` + `league/commonmark` | Langkah 8: PDF & Markdown |
| Storage | `local` disk untuk bukti transfer & export PDF |  |

---

## 5. User Flow Utama

### 5.1 Flow Member Baru → Jadi Paid
1.  Buka Landing (`/`) → Klik Daftar → Isi form → Otomatis login → Redirect `/member/dashboard` (Tier: default)
2.  Dashboard tunjuk banner: "Sisa 8/10 Generate — Upgrade ke Standart untuk Unlimited"
3.  Klik `Project → Buat PRD Baru` → Isi judul → Masuk Wizard Langkah 1
4.  Coba Generate AI di Langkah 1-2, kuota habis di Langkah 3 → Modal "Kuota Habis, Upgrade?"
5.  Masuk `Langganan → Pilih Standart (99k) → Lihat Rekening Transfer → Upload Bukti → Status Pending`
6.  Admin di `/admin/transaksi` klik Approve → Member dapat notifikasi + Tier naik ke `standart` + kuota reset 300x

### 5.2 Flow Pembayaran Transfer Manual
```
Member: Pilih Paket → Halaman Instruksi Transfer (BCA/Mandiri a/n BuatPRD) → Upload Bukti (jpg/png max 2MB) → Submit
System: Buat row `transactions` status=pending
Admin:  /admin/transaksi → Lihat bukti → Cek mutasi (manual) → Approve/Reject + catatan
System: Jika Approve → update `user_memberships` (tier_id, started_at, expired_at = now+30d) + tambah kuota → kirim notif
Member: /member/billing → status jadi Paid, invoice bisa download PDF
```

### 5.3 Flow Wizard PRD (Inti IDEA.md — di `/member/project/{id}/wizard/{step}`)
User bisa lompat step, tapi progress bar ingatkan yang kosong. Setiap step ada tombol `Bantu dengan AI ✨`.

| Step | Nama | Input User | Tombol AI | Output |
| :--- | :--- | :--- | :--- | :--- |
| **1** | Problem & Vision | Ide kasar (textarea) | `Ideation Partner` → Generate Problem Statement + Persona + Value Prop | Card Problem Statement tajam |
| **2** | Success Metrics | Pilih industri (e-commerce/SaaS/HRIS/manual) | `Metric Suggester` → Rekom KPI kuantitatif | Tabel KPI + target |
| **3** | Functional Requirements | Daftar fitur kasar | `Story Generator & MoSCoW` → Pecah jadi User Stories + Acceptance Criteria | List Story dengan Prioritas Must/Should |
| **4** | Diagram Fitur & Alur | Deskripsi alur | `Diagram-as-Code` → Generate Mermaid.js syntax | Preview flowchart/sequence live |
| **5** | Database Design | Daftar entity kasar | `Schema Generator` → Draf ERD + SQL/PostgreSQL/DBML | Code block + preview ERD |
| **6** | Non-Functional Req | Checklist kosong | `Checklist Generator` → Rekom keamanan/performansi/skalabilitas | Checklist + threshold latensi |
| **7** | UX & User Flow | Deskripsi UX kasar | `UX & Prompt Builder` → User Flow detail + Prompt untuk Figma/v0 | Flow text + prompt siap copy |
| **8** | Output & Versioning | Review semua step | `Document Compiler` → Rangkum jadi PRD rapi | Preview Markdown + Tombol Export PDF/Markdown + Version log (MVP vs Next) |

---

## 6. Functional Requirements — Dashboard & Menu (Production Ready)

### 6.1 `/admin/*` — Hanya Superadmin & Admin

**Layout:** Sidebar kiri (reuse `AuthenticatedLayout.vue`) — Active state violet, 2 level max.

| Menu | Submenu | Deskripsi Fungsional |
| :--- | :--- | :--- |
| **Dashboard** | Overview | Kartu: Total User, Total Project, Revenue Bulan Ini, Pending Transaksi. Grafik: User baru 30 hari, AI Usage global |
|  | Analytics | Tabel growth, retention, tier distribution (pie chart) |
| **User Management** | Semua User | Tabel paginated + search + filter role/tier. Action: Lihat, Edit, Suspend, Hapus (soft delete). Superadmin bisa ganti role jadi Admin |
|  | Staff (Superadmin only) | Khusus kelola akun Admin |
|  | Activity Log | `spatie/activitylog` — siapa ubah tier/transaksi, kapan, IP |
| **Membership & Paket** | Paket Management | CRUD `membership_tiers`: nama, harga, durasi, limit project/AI, fitur checklist (JSON). Drag reorder |
|  | Membership Member | Tabel `user_memberships`: user, tier saat ini, expired, sisa kuota. Tombol `Upgrade/Downgrade Manual` + `Perpanjang 30 Hari` |
|  | Kupon & Diskon | CRUD kode promo (ex: LAUNCH50 50% untuk 100 pemakaian pertama) |
| **Transaksi & Keuangan** | Daftar Pesanan | Tabel `transactions`: user, paket/kredit, nominal, bukti (preview image), status pending/approved/rejected, tanggal. Filter status. Action: Approve/Reject + catatan |
|  | Invoice | Generate invoice PDF per transaksi approved |
| **Konten & Template** | Kelola Landing | Edit teks hero, fitur, harga di Welcome.vue via DB (tanpa deploy) — V2 |
|  | Template PRD | CRUD template PRD siap pakai (ex: "Template SaaS HRIS") yang muncul di `/member/project` |
| **Bantuan** | Tiket Support | List tiket dari member: judul, status open/closed, assignee Admin. Balas via komentar |
|  | FAQ | CRUD FAQ untuk halaman Bantuan member |
| **Setting** (Superadmin) | Umum | Nama app, logo, kontak |
|  | Pembayaran | No. rekening (BCA/Mandiri), atas nama, instruksi transfer |
|  | AI Provider | API Key OpenAI/Gemini, model default, biaya per token (untuk hitung margin) |
|  | Keamanan | Maintenance mode, max upload bukti |

### 6.2 `/member/*` — Hanya Member (sesuai Tier)

| Menu | Submenu | Deskripsi Fungsional |
| :--- | :--- | :--- |
| **Dashboard** | — | Kartu personal: Sisa Kuota AI (ex: 42/100), Status Membership (Basic - exp 12 hari lagi - tombol Perpanjang), Total Project. List Project terbaru (3). Banner upgrade jika kuota <20% |
| **Project** | Semua Project | Tabel project milik user: judul, progress (ex: 5/8 langkah), status draft/selesai, updated_at. Search + filter status. Tombol `Buat PRD Baru` (cek limit tier dulu) |
|  | Template | Gallery template dari Admin. Klik `Gunakan Template` → duplicate jadi project baru |
|  | Sampah | Soft deleted projects, bisa Restore/Hapus permanen (auto hapus 30 hari) |
| **Profile** | — | Edit nama, email, password, avatar. Tampilkan Tier & expired |
| **Langganan & Billing** | Paket Saya | Card paket aktif + expired + sisa kuota |
|  | Upgrade Paket | Perbandingan 4 tier (pricing table) + tombol Pilih → ke flow transfer |
|  | Riwayat Tagihan | Tabel invoice: tanggal, paket, nominal, status, tombol Download PDF |
| **Wallet / Top Up** | Saldo Kredit | Card saldo kredit AI saat ini |
|  | Top Up Kredit | Pilih nominal 25k/50k/100k → instruksi transfer → upload bukti |
|  | Riwayat Transaksi | Tabel transaksi top up + langganan, status pending/approved/rejected |
| **Penggunaan** | Usage | Grafik batang AI generate per hari (30 hari), pie per langkah (Langkah 3 paling banyak), sisa kuota |
|  | Notifikasi | List notifikasi: "Pembayaran disetujui", "Kuota hampir habis", "Project selesai" |
|  | Bantuan | Form buat tiket: judul, kategori, deskripsi. List tiket saya + status |

**Aturan Tier Guard (Middleware `CheckTier`):**
*   `default` coba Export PDF → redirect ke `/member/langganan` + toast "Fitur ini butuh Basic ke atas"
*   `basic` coba buka Diagram Mermaid (Langkah 4) → toast "Butuh Standart ke atas"
*   Melebihi limit project → tombol `Buat PRD Baru` disabled + tooltip

---

## 7. Database Design (Production)

```sql
-- roles & permissions (spatie)
roles, permissions, model_has_roles, role_has_permissions

membership_tiers
- id, name (default/basic/standart/premium), slug, price (int), duration_days (30),
  limits (json: {max_projects, max_ai_per_month, can_export_pdf, can_mermaid, can_share}),
  features (json: ["Export PDF", "Mermaid"]), is_active, sort_order, created_at

user_memberships
- id, user_id (FK), tier_id (FK), status (active/expired/grace), started_at, expired_at,
  ai_quota_total, ai_quota_used, credit_balance (int), created_at
  -- 1 user 1 row aktif, history di transactions

transactions
- id, user_id, tier_id (nullable), type (subscription/topup), amount, payment_method (transfer),
  proof_path (nullable), status (pending/approved/rejected), admin_note, approved_by (admin_id),
  approved_at, created_at

projects (prd)
- id, user_id, title, slug, description, status (draft/in_progress/completed), current_step (1-8),
  progress (int 0-100), is_template (bool), template_id (nullable), deleted_at (soft)

project_sections
- id, project_id, step (1-8), title, content (longText / json), ai_generated (bool),
  ai_prompt (text), updated_at
  -- 1 row per step per project, jadi 8 row max per project

project_versions
- id, project_id, version (ex: 1.0 MVP, 1.1 Next), snapshot (json all sections), created_by, created_at

tickets
- id, user_id, subject, category, status (open/answered/closed), assigned_to (admin_id), created_at
ticket_replies
- id, ticket_id, user_id, message, created_at

activity_log (spatie)
- id, log_name, description, subject_type/id, causer_id, properties (json), created_at
```

**Invariants:**
1.  1 user hanya punya 1 `user_memberships` aktif. History tier ada di `transactions`.
2.  `project_sections` selalu 8 row per project (dibuat saat project dibuat, content kosong).
3.  Transaksi `pending` tidak menambah kuota sampai `approved`.
4.  Hanya Superadmin bisa ubah `roles` jadi `admin/superadmin`.

---

## 8. Non-Functional Requirements

*   **Keamanan:** Semua route `/admin/*` guard `role:superadmin|admin` + `auth` + `verified`. Upload bukti: mimes jpg/png/webp max 2MB, simpan di `storage/app/private/proofs`. Rate limit AI generate 10/min per user.
*   **Performa:** Dashboard query pakai pagination + eager load. AI generate async (queue `database` + loading state), tidak block UI. Cache `membership_tiers` 1 jam.
*   **Skalabilitas:** Siap untuk 5k user / 20k project di MySQL tanpa perubahan skema. File proof & export PDF di `storage`, siap pindah ke S3.
*   **Audit:** Semua perubahan tier/transaksi tercatat di `activity_log` (siapa approve, kapan).
*   **Backup:** Daily backup DB + storage (Laragon cron).

---

## 9. Integrations

*   **AI Provider:** `App\Services\AiService` — 1 service untuk semua 8 langkah, ganti provider cukup ubah env `AI_PROVIDER=openai|gemini`. Prompt template per langkah disimpan di `config/ai.php` atau DB.
*   **Email (future):** Notifikasi transaksi approved/rejected via `laravel/mail`. V1 bisa tanpa email, cukup notifikasi in-app.
*   **Storage:** `local` disk private untuk bukti & PDF. URL via signed route, bukan public.

---

## 10. Success Metrics (KPI Produk)

| KPI | Target 3 Bulan | Cara Ukur |
| :--- | :--- | :--- |
| Total User Terdaftar | 500 | `users` count |
| Paid Conversion (default → basic+) | >12% | `user_memberships where tier != default` / total users |
| Avg Project per Paid User | >3 | `projects` / paid users |
| AI Generation per Hari | >200 | `activity_log where log_name=ai-generate` |
| Transaksi Approved / Bulan | >50 | `transactions where status=approved` |
| Churn (tidak renew) | <35% | `user_memberships expired` tidak perpanjang |

---

## 11. Scope — In & Out

**In Scope (Production V1):**
*   Semua role, tier, dashboard, transaksi manual, wizard 8 langkah dengan AI, export PDF/Markdown, tiket support, kupon.

**Out of Scope (V2 - nanti):**
*   Payment gateway otomatis (Midtrans), OAuth Google, Kolaborasi real-time multi-user, Public share link PRD, API publik, Mobile app.

---

## 12. Roadmap Fase (Biar Sesi Baru Langsung Gas)

**Fase A — Fondasi (Sesi Berikutnya):** `spatie/permission` + migrasi `membership_tiers/user_memberships/transactions/projects` + seed Superadmin + pecah layout `/admin` & `/member` + guard tier.

**Fase B — Transaksi Manual:** CRUD Paket + Flow Upload Bukti + Approve Admin + Invoice PDF.

**Fase C — Wizard PRD (Inti IDEA.md):** CRUD Project + `project_sections` 8 langkah + UI Wizard + AI Service per langkah.

**Fase D — Polish Production:** Template, Sampah, Usage Grafik, Tiket, Kupon, Activity Log, Landing pricing table live.

---

**Lock Spec:** Dokumen ini adalah sumber kebenaran. Implementasi sesi baru wajib mengacu ke sini, jangan invent fitur di luar scope tanpa update PRD.


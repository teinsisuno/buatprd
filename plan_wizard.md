# Plan Wizard — Fokus Langkah 1-3 (Chat AI + 3 Tab Fungsional)

> Scope terkunci: L1 Problem & Vision + L2 Success Metrics sebagai chat AI, L3 Functional dengan 3 tab (Arsitektur, Modul, Struktur Folder). L4-L8 tetap placeholder. Sumber: `docs/PRD-BUATPRD-PRODUCTION.md:94` + `IDEA.md:4-6` + `config/ai.php:4` + `App\Services\AiService.php:22`

## 1. Tujuan

- Gantikan tombol single `Bantu AI ✨` di Langkah 1-2 menjadi chat iteratif: `textarea + attach file/gambar + dropdown model + send` (sesuai request user).
- Output tetap terstruktur agar Langkah 8 Document Compiler bisa compile rapi.
- Tetap patuh tier/quota (`PRD:3.2`) dan audit `activity_log`.

## 2. UX — Chat per Step

### 2.1 Route & State
- Route: `/member/projects/{project}/wizard/{step}` dengan `step` 1 atau 2. Guard `auth,verified`. Tier guard: step 1-2 tidak diblokir tier `default` (hanya limit project 2 dan AI 10x). Jika quota habis → modal upgrade (sesuai `PRD:5.1:4`).
- Layout: reuse `MemberLayout.vue` + `var(--bg-main)` canvas `context/ui-context.md:30`. Progress bar atas (5/8 langkah) warning jika kosong. Sidebar step list (klik lompat, tapi backend cek ownership).
- History: list bubble `user` (kanan, violet) vs `assistant` (kiri, card `var(--bg-card)`). Tiap bubble tampilkan model `provider:model`, timestamp, attachments preview. Input sticky bottom.

### 2.2 Input Bar
- `textarea` autosize, placeholder: L1 `"Jelaskan ide kasarmu... ex: Aplikasi kasir UMKM offline-first"` / L2 `"Industri & target angka... atau ketik 'suggest KPI untuk SaaS HRIS'"`
- Attach: button `+` → file picker `accept=".pdf,.txt,.md,.jpg,.png,.webp"` max 5MB/file, max 3 file/message. Preview chip + hapus. Simpan ke `storage/app/private/wizard/{project_id}/{uuid}.ext` (private, bukan public). Validasi `mimes:pdf,txt,md,jpg,png,webp`.
- Dropdown model: `GET /member/ai/models` (`Member\ProjectController` style `routes/web.php:96`) → grouped `AiProvider::activeModelsGrouped()` (`AiService.php:10`). Value format `provider:model` (ex: `openai:gpt-4o-mini`). Default = `AiService.php:15` `defaultProvider()->enabled_models[0]`. Jika attach image + pilih model text-only → backend auto fallback ke vision-capable model + toast info.
- Send: disabled jika textarea kosong + no attachment. Loading state `animate-slide-up`, abort controller 25s timeout.

## 3. Data Model

### 3.1 `project_sections` (`database/migrations/2025_08_29_100004_create_project_sections_table.php:7`)
- Kolom existing: `project_id, step, title, content (longText), ai_generated, ai_prompt` + unique `project_id+step` tetap.
- Perubahan: cast `content` jadi JSON di `App\Models\ProjectSection.php:12`:
  ```php
  'content' => 'array', // simpan { history: [...], final_output: {...}, updated_at }
  ```
  Tidak perlu migrasi baru (longText muat JSON). Alternatif jika butuh query: tambah `content_json` json nullable (optional, bisa tunda).
- Seeding: saat `Project::created` buat 8 row `ProjectSection::STEP_TITLES` (`ProjectSection.php:24`) dengan `content = { history:[], final_output:null }`.

### 3.2 Chat History Schema (disimpan di `content.history`)
```json
[
  {"role":"user","text":"...","attachments":[{"path":"wizard/1/abc.pdf","orig":"brief.pdf","extracted_text":"...","type":"pdf"}],"model":"openai:gpt-4o-mini","created_at":"2026-08-29T10:00:00Z"},
  {"role":"assistant","text":"... (rendered markdown)","structured":{"problem_statement":"..."},"model":"openai:gpt-4o-mini","mock":false,"created_at":"..."}
]
```
`final_output` = structured JSON terakhir yang valid (untuk compile Langkah 8).

## 4. Backend — Endpoint Chat

### 4.1 Route baru (`routes/web.php:80`)
```php
Route::post('/projects/{project}/wizard/{step}/chat', [WizardChatController::class, 'chat'])->name('projects.wizard.chat');
Route::get('/projects/{project}/wizard/{step}', [WizardChatController::class, 'show'])->name('projects.wizard.show');
Route::put('/projects/{project}/wizard/{step}/final', [WizardChatController::class, 'saveFinal'])->name('projects.wizard.final');
```
Middleware: `auth,verified` + `can:view,project` (policy ownership) + `throttle:10,1` (PRD:8 rate limit) + tier quota check.

### 4.2 `WizardChatController@chat` Flow
1. Validate: `project` milik `auth()->id()`, `step` in [1,2], `message` string max 5000, `attachments.*` file rules, `model` string nullable.
2. Quota check: `user_memberships.ai_quota_used < ai_quota_total + credit_balance` else 403 `quota_exceeded` → frontend modal upgrade.
3. Simpan upload private, ekstrak teks:
   - PDF: `smalot/pdfparser` atau `league/commonmark` fallback → `attachment_text`
   - TXT/MD: `Storage::get`
   - Image: jika provider vision → kirim base64 ke `AiService::callProvider` (perlu extend `AiService.php:85` support `image` part), jika bukan vision → ekstrak hint `"[Gambar: mockup dashboard, perlu OCR manual]"` + jangan block.
4. Ambil konteks: `Project.title/description`, `step1_content` jika step=2 (`ProjectSection where step=1`), `history` 3 terakhir.
5. Panggil `AiService::generate(step, enrichedInput, modelValue)` (`AiService.php:21`). `enrichedInput` = `['user_message'=>..., 'attachment_text'=>..., 'title'=>..., 'step1_content'=>..., 'history'=>...]`.
6. Jika response `error` → return 502 dengan `error_real` (mock fallback `AiService.php:40` tetap tampil tapi flag `mock:true`).
7. Simpan ke `project_sections.content.history` push user+assistant, update `final_output` jika JSON valid, set `ai_generated=true`, `ai_prompt=message`.
8. Log `activity()->log('ai-generate')` untuk KPI `PRD:10:38`.
9. Return JSON `{ history, final_output, mock, usage }`.

## 5. AI Prompt — Tambahan Aplikasi

### 5.1 Perubahan `config/ai.php:4` (expand)
Saat ini 1 baris (`config/ai.php:5`). Ubah jadi struktur:
```php
1 => [
  'name' => 'Problem & Vision',
  'system' => 'Kamu Ideation Partner BuatPRD. Ubah ide kasar jadi Problem Statement tajam + Persona + Value Prop Bahasa Indonesia, konkret, siap PRD. Aturan: jangan mengarang fitur di luar ide, jika info kurang beri [Asumsi] eksplisit, selalu output JSON valid sesuai schema, tone founder-friendly ringkas.',
  'user_template' => "KONTEKS PROJECT:\nJudul: {{title}}\nDeskripsi: {{description}}\n\nINPUT USER (L1):\n{{user_message}}\n\nLAMPIRAN TEKS:\n{{attachment_text}}\n\nHISTORY SINGKAT:\n{{history}}\n\nINSTRUKSI: Hasilkan 3 bagian + clarifying question. Jika lampiran screenshot, deskripsikan masalah terlihat.",
  'json_schema' => '...',
],
2 => [
  'system' => 'Kamu Metric Suggester BuatPRD. Rekomendasi KPI kuantitatif SMART spesifik industri (e-commerce/SaaS/HRIS/marketplace/edtech/fintech/manual). KPI harus ada target angka + cara ukur.',
  'user_template' => "RINGKASAN L1:\n{{step1_content}}\n\nINPUT USER L2:\n{{user_message}}\nIndustri dipilih: {{industry_or_auto}}\nLAMPIRAN:\n{{attachment_text}}\n\nINSTRUKSI: 5-7 KPI, bedakan Leading vs Lagging, sertakan North Star + baseline asumsi, instrumentasi event.",
]
```
Template dirender di `AiService::generate` sebelum `callProvider` (`AiService.php:29`). `{{history}}` = 3 turn terakhir stringified, `{{step1_content}}` = `final_output` step 1 json→text.

### 5.2 Vision Support
Extend `AiService.php:85` `callProvider` untuk handle `attachments` image: OpenAI/Gemini/Anthropic terima `image_url` base64. Jika `AiProvider` bukan vision → tetap text-only + warning.

## 6. Response yang Diharapkan (JSON Strict)

### 6.1 Langkah 1 — Problem & Vision
```json
{
  "problem_statement": "1 paragraf max 3 kalimat, tajam",
  "target_personas": [{"nama":"Admin UMKM","deskripsi":"...","pain":"...","goal":"..."}],
  "value_proposition": "1 kalimat UVP",
  "pain_points": ["..."],
  "asumsi": ["[Asumsi] ..."],
  "pertanyaan_klarifikasi": ["Apakah target awal hanya offline-first?"]
}
```
Render frontend: Card Problem Statement (gradient violet), list Persona (avatar placeholder), Value Prop highlight champagne, Asumsi badge warning.

### 6.2 Langkah 2 — Success Metrics
```json
{
  "industri_terdeteksi": "SaaS",
  "kpis": [
    {"nama":"Activation Rate","definisi":"% user selesaikan onboarding","target":"60% dalam 7 hari","tipe":"Leading","cara_ukur":"event onboarding_complete","prioritas":"Must"}
  ],
  "north_star": {"metric":"Weekly Active Projects","alasan":"..."},
  "rekomendasi_instrumentasi": ["Mixpanel event ..."],
  "asumsi_baseline": ["..."]
}
```
Render: Tabel KPI (kolom Nama | Target | Tipe badge | Cara Ukur) + North Star card + instrumentasi checklist. Sesuai `PRD:100` tabel KPI + target.

Paksa `response_format: {type:"json_object"}` di `AiService.php:109` untuk OpenAI/Groq/OpenRouter. Untuk Gemini/Anthropic tambahkan instruksi `"HANYA output JSON valid, tanpa markdown fence"`.

## 7. Frontend — Komponen

- `resources/js/Pages/Member/Wizard/Show.vue` (reuse `MemberLayout.vue`): header project title + stepper 1-8 (active violet), progress %, chat container `max-w-3xl`, input bar sticky.
- `resources/js/Components/Wizard/ChatBubble.vue`, `AttachmentPreview.vue`, `ModelSelector.vue` (dropdown grouped), `StructuredOutputCard.vue` (render JSON → UI).
- State: `useForm` Inertia atau `fetch` untuk chat (multipart). Polling tidak perlu (sync 25s). Simpan `final_output` ke card "Ringkasan Terstruktur" yang bisa `Simpan sebagai Final` (PUT final) → update `progress` project.
- Empty state: L1 `"Belum ada chat, coba: 'Aku mau bikin app kasir UMKM yang bisa offline...'"` ; L2 `"Lanjutkan dari Problem di Langkah 1, atau ketik industri: SaaS HRIS"`

## 8. Keamanan & Quota

- Policy: `ProjectPolicy` cek `project.user_id == auth.id()` untuk `show/chat`.
- Quota: increment `user_memberships.ai_quota_used` + `credit_balance` decrement jika quota paket habis (logic existing `TransactionController approve`). Hitung 1 credit = 1 generate (`PRD:3.2:53`).
- Audit: `activity()->causedBy(auth)->performedOn(project)->log('wizard-chat step 1')`.
- File: private disk `local`, url via signed route `storage.local` (`routes/web.php:96`).

## 9. Testing & Verifikasi

- `php artisan test` tetap 25 pass (tambah feature test `WizardChatTest`: owner bisa chat, non-owner 403, quota habis 403, attachment pdf 5MB pass, image vision fallback).
- `npm run build` pass (cek bundle `Wizard/Show` < 20kB).
- Manual: login `member@buatprd.test / password` (seed `DatabaseSeeder.php`) buat project, coba L1 chat + attach pdf + ganti model `openai:gpt-4o-mini` → cek `project_sections step=1 content.history` terisi, L2 auto bawa `step1_content`.

## 10. Langkah 3 — Functional (3 Tab: Arsitektur | Modul | Struktur Folder)

> Perluasan dari `IDEA.md:6` Story Generator & MoSCoW → jadi 3 tab agar output L3 langsung actionable untuk dev. Tetap pakai 1 `project_sections` row `step=3`, tapi `content.final_output` berisi 3 objek.

### 10.1 Tab 1 — Arsitektur

**Tujuan:** Pilih pola arsitektur berdasarkan stack yang user pilih/deteksi dari L1-L2.

- Opsi: `Monolit`, `Modular Monolit`, `Microservice`, `Serverless`, `Hybrid` (pilihan dinamis dari AI, bukan hardcode 5 saja — AI boleh sarankan lain ex: `Event-driven` jika cocok).
- Input stack: dropdown multi-select `Laravel`, `Next.js`, `Vue`, `React`, `Golang`, `Node`, `Supabase`, `MySQL/Postgres` + field `Preferensi` (ex: "team kecil 2 dev, mau deploy di VPS"). Jika user kosong, AI deteksi dari L1 (ex: "kasir offline" → rekomendasikan Modular Monolit Laravel + SQLite sync).
- AI generate: `architecture` JSON
  ```json
  {
    "recommended": "modular_monolith",
    "stack": ["Laravel 11","Inertia Vue","MySQL"],
    "alasan": "team kecil, butuh transaksi ACID, deploy simpel...",
    "opsi": [
      {"id":"monolith","label":"Monolit","pro":["deploy simpel"],"kontra":["skala tim susah"],"cocok":true},
      {"id":"microservice","label":"Microservice","pro":["scale independen"],"kontra":["overkill 2 dev"],"cocok":false}
    ],
    "diagram_text": "Monolith -> Modules: Auth, POS, Inventory"
  }
  ```
- Prompt L3-Tab1 `config/ai.php:4` inject `{{step1_final}} + {{step2_final}} + {{stack_pilihan}}`. System: "Kamu Software Architect. Pilih arsitektur paling pragmatis untuk tim kecil-menengah Indonesia, jelaskan trade-off jujur, jangan selalu microservice."
- UI: radio cards besar per opsi (ikon + pro/kontra), badge `Rekomendasi AI` di pilihan `recommended`. Tombol `Gunakan Rekomendasi` → set `final_output.architecture.selected`.

### 10.2 Tab 2 — Modul (Checkbox List + Custom)

**Tujuan:** Daftar modul/fitur yang akan dibangun, bisa checklist saran AI, tambah modul sendiri, atau tulis manual.

- AI generate: `modules` array dari konteks L1-L2 + arsitektur terpilih:
  ```json
  {
    "modules": [
      {"id":"auth","nama":"Auth & Role","deskripsi":"Login, RBAC superadmin/admin/member","prioritas":"Must","estimasi":"3 hari","checked":true},
      {"id":"pos","nama":"POS Offline","deskripsi":"Transaksi kasir offline-sync","prioritas":"Must","checked":true},
      {"id":"reporting","nama":"Laporan","deskripsi":"Omzet harian","prioritas":"Should","checked":false}
    ]
  }
  ```
  Prioritas ikut MoSCoW (`Must/Should/Could/Won't`) sesuai `PRD:5.3:101`.
- UI:
  - List checkbox (group per prioritas, badge Must violet, Should champagne) — default checked = Must/Should saran AI.
  - Bar atas: `+ Tambah Modul` (modal nama/deskripsi/prioritas) dan `Tulis Daftar Modul Sendiri` (textarea bulk, 1 baris = 1 modul, parser `nama - deskripsi`).
  - Opsi edit/hapus per modul, drag reorder (sort_order simpan).
  - Counter `Terpilih 6/9 modul` + estimasi total.
- Simpan: `final_output.modules` = array final (checked + custom). Ini jadi sumber untuk Tab 3 dan untuk L4 Diagram.

### 10.3 Tab 3 — Struktur Folder Dinamis (BISA, dan direkomendasikan)

**Jawaban: Bisa dibuat dinamis sepenuhnya, tanpa buat file fisik.** 

- Model: `folder_structure` JSON tree, bukan folder beneran di disk.
  ```json
  {
    "root": "buat-kasir-umkm",
    "tree": [
      {"name":"app","type":"folder","children":[
        {"name":"Modules","type":"folder","children":[
          {"name":"Auth","type":"folder","children":[{"name":"Controllers","type":"folder"},{"name":"Models","type":"folder"}]},
          {"name":"Pos","type":"folder","children":[{"name":"Services","type":"folder"}]}
        ]},
        {"name":"Http","type":"folder"}
      ]},
      {"name":"resources/js","type":"folder","children":[{"name":"Pages","type":"folder"}]},
      {"name":"README.md","type":"file"}
    ]
  }
  ```
- Generate: AI terima `{{architecture.selected}} + {{modules}} + {{stack}}` → output tree sesuai konvensi stack (Laravel modular monolith → `app/Modules/{Modul}`, Next.js → `app/(modules)/`). Prompt: "Generate struktur folder idiomatik untuk stack terpilih, 2-3 level saja, jangan terlalu dalam, sertakan file kunci."
- UI dinamis (Vue):
  - Komponen rekursif `FolderTree.vue` — render indent + ikon folder/file, expand/collapse, hover action `+ Folder | + File | Rename | Hapus`.
  - Toolbar: `Tambah di Root`, `Reset ke Saran AI`, `Copy sebagai Text`, `Download ZIP` (backend generate ZIP dari JSON via `ZipArchive` on-the-fly, tidak simpan permanen).
  - Edit inline: klik nama → input, validasi `^[a-zA-Z0-9._-]+$`.
  - State disimpan di `final_output.folder_structure.tree` (array), tiap perubahan `PUT /wizard/3/final` debounce 800ms.
- Kelayakan: 100% frontend, beban server hanya JSON (<10KB) + ZIP generate <100ms untuk 50 node. Tidak butuh migrasi baru, tidak butuh filesystem. Tier guard: semua tier bisa lihat, tapi `default` tidak bisa Download ZIP (butuh `basic` ke atas sesuai `PRD:6:44`).

### 10.4 Prompt & Response Kontrak L3

- Single call AI untuk 3 tab sekaligus (hemat kredit): `AiService::generate(3, {step1_final, step2_final, stack, pesan_user}, model)` → return 1 JSON berisi `architecture + modules + folder_structure`.
- Fallback: jika user hanya edit manual Tab 2/3 tanpa AI, tidak hitung kredit, hanya simpan.
- Validasi: `folder_structure` harus valid tree (max depth 5, max nodes 80) — jika AI ngaco, backend fallback ke template per stack di `config/ai.php`.

## 11. Dependency Antar Langkah (Kunci)

- `final_output` Langkah 1 + 2 adalah **sumber konteks wajib** untuk Langkah 3 3-tab di atas. Prompt L3 inject `{{step1_final}} + {{step2_final}}`:
  ```php
  3 => ['user_template' => "KONTEKS L1:\n{{step1_final}}\nKONTEKS L2:\n{{step2_final}}\nSTACK PILIHAN:\n{{stack}}\nINPUT L3:\n{{user_message}}"]
  ```
- Jika user lompat L1/L2, backend kirim `[Asumsi] step kosong` agar L3 tetap generate tapi flag `asumsi` explicit.
- `final_output` L3 (`architecture.selected + modules + folder_structure`) jadi konteks wajib L4 Diagram & L5 DB Design.

## 12. Hal di Luar Scope (Tunda)

- Langkah 3-8 tetap ComingSoon, tidak disentuh (hanya kontrak dependency di atas yang dikunci).
- OCR full image, realtime streaming, drag-drop kanban, versioning snapshot (`project_versions`) untuk L1-2 belum perlu.
- Landing pricing live, template gallery (Fase D).

## 13. Langkah Eksekusi Berurutan

1. Update `config/ai.php` + `AiService.php` vision + `ProjectSection` cast (cover L1-L3 schema)
2. Buat `WizardChatController` + routes + policy + throttle (support `step` 1,2,3 + `saveFinal` untuk 3 tab)
3. Buat `Show.vue` + komponen chat L1-L2 (1 PR verifiable)
4. Buat 3-tab L3 (`ArchitectureCards.vue` + `ModuleChecklist.vue` + `FolderTree.vue`) (PR terpisah)
5. Uji quota + attachment + model dropdown + folder ZIP download

# Wizard Spec v2 — BuatPRD Guided Builder

> **v2** menggantikan v1 (30 Aug 2026, "chat iteratif"). Keputusan arsitektur dikunci bersama user (Tengku), 30 Aug 2026.
> Sumber: diskusi desain + `PRD-BUATPRD-PRODUCTION.md` + `wiz_prom_engineering.md` + pengalaman implementasi v1.

---

## 1. Prinsip & Pergeseran Konsep

| | v1 (lama) | **v2 (baru, dikunci)** |
|---|---|---|
| Pola interaksi | Chat iteratif di SEMUA langkah | **Guided Builder**: AI memandu dgn pilihan, user memutuskan |
| L1 | Chat terbuka | **Ide (satu-satunya input terbuka)** |
| L2 | Chat terbuka "jelaskan industri/angka" | **Klarifikasi terpandu**: AI kasih checkbox/pilihan + field custom, max turn |
| L3 | Chat terbuka (stack/modul) | **Finalisasi**: AI rangkum skeleton PRD + field perbaikan |
| L4+ | Coming soon (prompt generik) | **Canvas editor** (JSON Master + Vue Flow + Mermaid) |
| SSOT | `project_sections.content` | `project_sections.content` (JSON per step) + **JSON Master** khusus L4-L5 |

**Prinsip inti:** hanya L1 yang "user bercerita". L2-L7 = *review & refine*: AI buat draf, user koreksi/kompromi, hingga Simpan Final. L8 = compiler (bukan chat).

---

## 2. Alur & Detail Per Langkah

### L1 — Ide (input terbuka) 🗣️
- **Input:** textarea bebas + lampiran `pdf,txt,md,jpg,png,webp` (max 3 × 5MB) + URL clone (backend fetch, sanitasi SPA script/style, 5k char).
- **AI:** rangkum ide, ekstrak fitur inti. Jika ada URL clone → WAJIB ekstrak fitur & pertanyaan diferensiasi (v1 sudah, dipertahankan).
- **Final L1:** `{ ringkasan_ide, fitur_inti[], target_pengguna_awal, lampiran_info[] }` — ringan, fungsinya jadi **bahan bakar L2**.
- Chat bebas (bukan dibatasi turn), user klik **Simpan Final** untuk lanjut.

### L2 — Klarifikasi Terpandu ✅ (keputusan #1)
- **Generator pertanyaan: Dynamic AI** — AI menganalisis `final_output L1` (teks/lampiran unik user) lalu menghasilkan pertanyaan relevan tiap turn. Bukan kuesioner statis.
- **Format tiap turn (AI → user):**
  ```json
  {
    "questions": [
      { "id": "q1", "text": "User Roles?", "options": ["admin","petugas","merchant/kasir","member"], "allow_custom": true },
      { "id": "q2", "text": "Dashboard?", "options": ["multi dashboard by roles","single dashboard + guard"], "allow_custom": true }
    ],
    "note": "Pilih yang sesuai; jika kurang, tulis pendapatmu."
  }
  ```
- **UI:** kartu pilihan (checkbox) per pertanyaan + field **"tulis pendapat anda sendiri"** per kartu (selalu ada). User kirim jawaban → AI analisis → turn berikutnya (pertanyaan lanjutan / gali jawaban custom).
- **Turn counter: per project-step**, tersimpan di state DB (`content.clarify.turn`), bukan per sesi browser.
- **Max turn:** 3 (default) — setting di `/admin` (config `ai.wizard.max_clarify_turns`, nanti diekspos UI admin). Setiap turn = 1 kredit (max 3 kredit di L2).
- **Tombol "Lanjut ke Langkah 3":** muncul otomatis bila:
  - (a) jawaban turn terakhir **tidak mengandung custom** di semua pertanyaan, ATAU
  - (b) `turn >= max_turn` (force).
  - Jika ada custom & masih di bawah max → AI satu turn lagi menggali custom (tombol tetap tersembunyi).
- **Final L2:** `{ answers: [ {question_id, question, selected[], custom} ], turn, max_turn }`.

### L3 — Finalisasi Skeleton PRD 📋
- AI rangkum L1+L2 → **draft skeleton PRD lengkap** (schema Lampiran A): roles, dashboards, modul utama, target user, dst.
- UI menampilkan draft terstruktur + **textarea "Perbaikan"** → user kirim revisi → AI revisi → rangkum final.
- **Final L3:** `skeleton_prd` = **SSOT utama untuk L4-L7**.

### L4 — Diagram & Canvas 🕸️ ✅ (keputusan #2)
- **SSOT: JSON Master `{nodes[], edges[]}`** (format Vue Flow) tersimpan di `content.json_master`, per tab.
- **2 tab diagram:**
  1. **By Modul** — struktur fitur/modul & relasinya
  2. **By Menu** — alur menu/flow penggunaan (bisa jadi panduan penggunaan aplikasi utk non-developer)
- **AI generate draf awal** dari skeleton L3 (kedua tab), lalu user:
  - **Edit via Canvas:** drag & drop, CRUD node/edge (add/rename/delete), template starter.
  - **Edit via Chat box:** perintah bahasa natural ("tambah alur login → dashboard petugas") → backend AI → mutasi JSON Master.
- **Render/export:** `MermaidConverter` (backend service) → JSON Master → sintaks Mermaid (flowchart) untuk L8.
- **Final L4:** JSON Master + mermaid hasil convert.
- Kredit: edit canvas **gratis**; pesan chat bantuan = 1 kredit.

### L5 — Database Design 🗄️
- AI desain dari **JSON Master L4** (node bertipe entity/relasi) → `entities[]` (fields, tipe, unique, relasi).
- UI: editor entity (CRUD entity/field/relation) + **chat box AI** utk edit.
- Output: `entities[], dbml, sql, diagram_mermaid` (ERD) → **Final L5**.
- Pertahankan handling tenancy v1 (jika user sebut multi-tenancy → AI wajib rekomendasi pola, jangan nurut).

### L6 — Desain UI 🎨 ✅ (keputusan #3)
- **Opsi 1 (dikunci):** AI hasilkan **daftar screen + system prompt Figma/v0/Bolt.new** — user copy-paste ke v0.dev/Lovable/Bolt untuk generate UI nyata.
- Tambahan per screen: **ringkasan user flow** + **wireframe layout ASCII/JSON sederhana** (developer tetap paham layout tanpa render).
- Output: `screens[] { nama, tujuan, user_flow[], wireframe_ascii, wireframe_json, prompt_v0 }`.

### L7 — Non-Functional Requirements 🛡️
- AI beri **checklist** keamanan/performansi/skalabilitas + `threshold {latensi, uptime, backup, retensi}`.
- UI: checklist centang/editable + chat refine.
- Output: `checklist{}, threshold{}`.

### L8 — Output & Versioning 📦 (compiler)
- **Compiler** (bukan chat): tombol `Compile` → gabung final_output L1-L7 → **PRD Markdown** (diagram L4/L5 dirender dari Mermaid).
- `version { 1.0 MVP, 1.1 Next }` + **Export PDF** (dompdf, sudah terpasang) / Markdown.
- Simpan ke `project_versions` (sudah ada tabel).

---

## 3. State & Data

### 3.1 `project_sections.content` per step (v2)
```jsonc
// L2 (contoh)
{ "clarify": { "turn": 2, "max_turn": 3 },
  "history": [/* messages AI questions + user answers */],
  "final_output": { "answers": [/* ... */] } }

// L4 (contoh)
{ "history": [],
  "json_master": { "tabs": { "modul": { "nodes": [], "edges": [] }, "menu": { "nodes": [], "edges": [] } } },
  "final_output": { "json_master": "…sama…", "mermaid": { "modul": "flowchart…", "menu": "flowchart…" } } }
```

### 3.2 JSON Master schema (Lampiran B)
```json
{ "meta": { "version": 1 },
  "nodes": [ { "id": "n1", "type": "modul|menu|screen|action|entity|note", "label": "…", "position": { "x": 0, "y": 0 }, "data": {} } ],
  "edges": [ { "id": "e1", "source": "n1", "target": "n2", "label": "…", "type": "default|step" } ] }
```

### 3.3 Migrasi project v1
- Project lama (TOYAA PAMS, content L1-L3 schema v1) **tetap bisa dibaca** (render fallback v1), tidak dipaksa migrasi.
- Project baru memakai schema v2. Keputusan final saat implementasi P1 (opsi: tombol "Reset wizard" keras vs preserve-read).

---

## 4. Prompt Engineering (Admin)

- `wizard_prompts` tetap per step (system + user_template + json_schema + versioning/rollback).
- Variabel baru: `{{answers}}` (L3), `{{skeleton_prd}}` (L4-L7), `{{json_master}}` (L5), `{{screens}}` (L7).
- L2: system prompt berisi schema `questions[]` (Lampiran C) + aturan dynamic-per-turn + gali custom.
- `max_clarify_turns` via `config/ai.php` + ekspos di UI admin (settings/wizard-prompts).

## 5. Kredit & Limit

| Step | Biaya | Limit |
|---|---|---|
| L1 | 1 chat = 1 kredit | max 10 chat/step |
| L2 | 1 turn = 1 kredit | max `max_clarify_turns` (3) |
| L3+ | 1 chat = 1 kredit (edit canvas gratis) | max 10 chat/step |
| Global | — | throttle 10/min, tier guard tetap |

---

## 6. Breakdown Implementasi Bertahap

### ✅ Prioritas 1 — L1-L3 (core Guided Builder)
1. **Schema & migrasi:** struktur content v2 per step (L1/L2/L3) + render fallback v1; `config/ai.php` tambah `max_clarify_turns`.
2. **WizardController:** mode per step — L1 `open_input`, L2 `guided_choice`, L3 `finalize`. Endpoint: chat L1 stabil; **L2: `GET questions` (AI generate) + `POST answers` (simpan turn, hitung lanjut) + `next_available` flag**; L3: `rangkum` + `revisi`.
3. **PromptResolver:** var `{{answers}}` + schema L2 questions; update prompt L1-L3 di DB (via seeder/update) + versi baru wizard_prompts (tanpa rollback force).
4. **Frontend:** `QuestionCard.vue` (checkbox + custom input), alur turn L2, tombol "Lanjut ke Langkah 3" logic; L3: draft render + field perbaikan; L1: polish bubble (AssistantBubble sudah ada).
5. **Test:** WizardChatTest v2 (turn counter, custom gate, force max_turn), build, seed ulang.

### ✅ Prioritas 2 — L4-L5 (Canvas & Database)
1. **Backend JSON Master:** controller CRUD node/edge per tab + validasi + `MermaidConverter` service (+ unit test).
2. **Frontend Canvas:** integrasi **@vue-flow/core** (Vue 3 native, rekomendasi; alternatif Cytoscape) — 2 tab, drag, CRUD, template starter.
3. **Chat-edit agent:** endpoint NL → AI → mutasi JSON Master (pattern: prompt berisi JSON Master utuh + instruksi "kembalikan JSON Master baru").
4. **L5:** AI generate entities dari JSON Master + entity editor UI + DBML/SQL/mermaid preview.
5. **Test:** CRUD canvas, konversi mermaid, chat-edit roundtrip.

### ✅ Prioritas 3 — L6-L8 (UI, NFR, Output)
1. **L6:** screens + wireframe ASCII/JSON + prompt v0/Figma/Bolt; UI daftar screen + copy.
2. **L7:** checklist NFR + threshold editor + chat refine.
3. **L8 compiler:** gabung final_output → markdown PRD (Mermaid embedded) → export PDF (dompdf) / MD → `project_versions`.
4. **Test & polish** end-to-end TOYAA.

---

## 7. Keputusan Terbuka (dikunci saat implementasi)

1. Lokasi setting `max_clarify_turns`: `config/ai.php` saja vs diekspos UI admin (rekomendasi: config + input kecil di halaman wizard-prompts admin).
2. Migrasi project v1: preserve-read vs tombol reset (rekomendasi: preserve-read + reset manual).
3. Format wireframe L6: `wireframe_ascii` + `wireframe_json` (rekomendasi), ukuran prompt dijamin < 4k char/prompt.
4. Package canvas: **@vue-flow/core** (Vue 3 native, ringan, SSR-friendly) — fallback Cytoscape jika butuh fitur layout otomatis kompleks.

---

## Lampiran A — Skeleton PRD (final L3, SSOT L4-L7)
```json
{ "produk": { "nama": "", "tagline": "" },
  "roles": [ { "id": "", "nama": "", "deskripsi": "" } ],
  "dashboards": [ { "nama": "", "untuk_role": [], "modul_utama": [] } ],
  "modul_utama": [ { "id": "", "nama": "", "deskripsi": "", "prioritas": "Must|Should|Could" } ],
  "target_pengguna": [ "" ],
  "asumsi": [ "[Asumsi] …" ],
  "pertanyaan_belum_terjawab": [ "" ] }
```

## Lampiran B — JSON Master (L4)
```json
{ "meta": { "version": 1, "tab": "modul|menu" },
  "nodes": [ { "id": "n1", "type": "modul|menu|screen|action|entity|note", "label": "", "position": {"x":0,"y":0}, "data": {} } ],
  "edges": [ { "id": "e1", "source": "n1", "target": "n2", "label": "", "type": "default|step" } ] }
```

## Lampiran C — L2 Questions (output AI per turn)
```json
{ "questions": [
    { "id": "q1", "text": "", "options": ["…"], "allow_custom": true } ],
  "note": "" }
```
User answer: `{ "answers": [ { "question_id": "q1", "selected": ["admin"], "custom": "" } ] }`
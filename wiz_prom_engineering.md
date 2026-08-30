# Wizard Prompt Engineering — Plan

> Superadmin bisa edit prompt per step (1–8) via UI, tanpa deploy. Prompt jadi sumber kebenaran untuk `AiService`. Fallback ke `config/ai.php` jika DB kosong.
> File: `wiz_prom_engineering.md` | Owner: Tengku | Status: PLAN (belum eksekusi) | Tanggal: 30 Aug 2026

---

## 1. Tujuan

- Superadmin kelola **System Prompt + User Template + JSON Schema + Nama Step** untuk 8 langkah wizard langsung dari `/admin/wizard-prompts`.
- Prompt editable tanpa redeploy, dengan **preview, test, versioning, dan audit** agar tidak merusak wizard member.
- Member tetap pakai wizard seperti biasa — tidak ada perubahan UX wizard di fase ini, hanya sumber prompt yang berpindah dari file config ke DB.

**Out of scope fase ini:** ubah flow wizard, ubah `projects`/`project_sections`, ubah AI provider, streaming, prompt per-user.

---

## 2. Kondisi Saat Ini (As-Is)

- `config/ai.php` hardcode `prompts[1..8]` — tiap step punya `name`, `system`, `user_template`, `json_schema` (step 4–8 masih placeholder).
- `AiService::generate()` baca via `config('ai.prompts')` → `renderPrompt()` ganti `{{var}}` via `str_replace` + bersihkan sisa `{{...}}`.
- Variabel yang dipakai (dari `AiService::renderPrompt` + `WizardController::chat` yang sudah dihapus, tapi akan dipakai lagi):
  `title`, `description`, `user_message`, `attachment_text`, `history`, `step1_content/step1_final`, `step2_final`, `stack`, `industry_or_auto`, `images[]`.
- Tidak ada UI edit, tidak ada versioning, tidak ada test playground.

---

## 3. Requirements (To-Be)

### 3.1 Functional

| ID | Kebutuhan | Catatan |
|---|---|---|
| F1 | CRUD prompt per step 1–8 | Hanya Superadmin. Satu row per step, step unik. |
| F2 | Field editable: `step` (1–8, immutable), `name`, `system`, `user_template`, `json_schema`, `is_active` | `is_active=false` = wizard pakai fallback config + banner warning |
| F3 | Variable helper | Chip daftar variabel valid per step, klik untuk insert `{{var}}`. |
| F4 | Preview | Render `user_template` dengan dummy data (lihat §6) secara live di samping editor. |
| F5 | Test | Tombol “Test Prompt” → panggil `AiService::generate(step, dummyInput, defaultModel)` dengan key default Utama/Vision, tampilkan raw response + parsed JSON + latency. Tidak simpan ke project. |
| F6 | Validasi | `system` required, `user_template` required, `json_schema` harus JSON valid (jika diisi), tidak boleh ada variabel tak dikenal. |
| F7 | Versioning | Setiap save buat versi baru (snapshot), bisa lihat history & rollback 1 klik. |
| F8 | Audit | Tiap create/update/rollback log ke `activity_log` (causer, step, diff). |
| F9 | Fallback | Jika DB tidak ada row untuk step → pakai `config/ai.php`. Jika `user_template` kosong → fallback juga. |

### 3.2 Non-Functional

- **Permission:** middleware `role:superadmin` saja (Admin biasa tidak boleh ubah prompt).
- **Rate limit test:** 5 test / menit per superadmin (pakai `throttle:5,1`).
- **Keamanan:** `user_template` & `system` di-escape saat render preview (XSS), tapi disimpan raw. Tidak boleh inject PHP.
- **Performa:** prompt di-cache 60 detik (`Cache::remember("wizard_prompt:{step}")`), invalidate on save.

---

## 4. Arsitektur

```
[config/ai.php] --seed--> [DB wizard_prompts] --cache--> [PromptResolver] --> [AiService::renderPrompt()]
                                   ^                           |
[ /admin/wizard-prompts UI ] ------+-----> [WizardPromptController] --> [wizard_prompt_versions] + activity_log
```

- `PromptResolver` adalah layer tipis: `WizardPrompt::forStep($step)` → cari DB aktif, jika tidak ada → `config("ai.prompts.$step")`.
- `AiService` tidak lagi baca `config('ai.prompts')` langsung, tapi `PromptResolver::get($step)`. Logic `renderPrompt` tetap sama (str_replace variabel).
- Seeder: saat migrate fresh, isi `wizard_prompts` dari `config/ai.php` (8 row) + buat `wizard_prompt_versions` v1.

---

## 5. Data Model

### 5.1 Tabel `wizard_prompts`

```php
Schema::create('wizard_prompts', function (Blueprint $table) {
    $table->id();
    $table->tinyInteger('step')->unique(); // 1..8
    $table->string('name', 80);
    $table->text('system'); // system prompt
    $table->longText('user_template'); // dengan {{var}}
    $table->text('json_schema')->nullable(); // string JSON
    $table->boolean('is_active')->default(true);
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

Index: `unique(step)`.

### 5.2 Tabel `wizard_prompt_versions` (history)

```php
Schema::create('wizard_prompt_versions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('wizard_prompt_id')->constrained()->cascadeOnDelete();
    $table->unsignedInteger('version'); // 1,2,3...
    $table->text('system');
    $table->longText('user_template');
    $table->text('json_schema')->nullable();
    $table->string('name', 80);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->text('change_note')->nullable(); // opsional
    $table->timestamps();
    $table->unique(['wizard_prompt_id','version']);
});
```

### 5.3 Model

- `WizardPrompt extends Model` — fillable `step,name,system,user_template,json_schema,is_active,updated_by`, casts `is_active=>bool`, relasi `versions()`, `updater()`, method `static forStep($step): array` (return array shape sama dengan config).
- `WizardPromptVersion extends Model` — belongsTo prompt.

### 5.4 Seeder

`Database\Seeders\WizardPromptSeeder` → loop `config('ai.prompts')` insert + versi 1. Dipanggil dari `DatabaseSeeder`.

---

## 6. Variabel Template (Kontrak)

Daftar variabel resmi yang boleh dipakai di `user_template`. Resolver akan replace, sisa `{{unknown}}` → `"-"` .

| Var | Sumber | Dipakai Step | Contoh |
|---|---|---|---|
| `{{title}}` | `projects.title` | 1–8 | “Kasir UMKM” |
| `{{description}}` | `projects.description` | 1–8 | “App offline-first …” |
| `{{user_message}}` | input chat member langkah itu | 1–8 | “owner rekap manual di buku” |
| `{{attachment_text}}` | ekstraksi PDF/txt/gambar (OCR teks) | 1–8 | “(teks dari file)” |
| `{{history}}` | 3 turn terakhir `project_sections` | 1–8 | “USER: …\nASSISTANT: …” |
| `{{step1_content}}` / `{{step1_final}}` | final_output step 1 | 2,3 | JSON string |
| `{{step2_final}}` | final_output step 2 | 3 | JSON string |
| `{{stack}}` | pilihan stack member | 3 | “Laravel, Vue” |
| `{{industry_or_auto}}` | industry atau auto-deteksi | 2 | “SaaS” |
| `{{industry}}` | alias `industry_or_auto` | 2 |  |
| `{{project_title}}` | alias `title` | — |  |

**Aturan:** 
- Step 1 hanya butuh `title, description, user_message, attachment_text, history`.
- Step 2 butuh + `step1_content, industry_or_auto`.
- Step 3 butuh + `step1_final, step2_final, stack`.
- Step 4–8 (placeholder) butuh semua di atas + agregat `all_steps` (akan ditambah nanti).

Validator di controller: cek `preg_match_all('/\{\{(\w+)\}\}/', $template, $m)` → tiap var harus ada di whitelist global. Warning jika tidak.

---

### 6.1 Handling Khusus — Clone URL (contoh: "TOYAA seperti https://meterpams.com" di L1)

- Backend deteksi `https://` di `user_message` → `Http::timeout(8)->get(url)` → `strip_tags` → plain text 5k char → inject ke `{{attachment_text}}` sebelum `AiService::generate()`. Lihat `docs/WIZARD-CHAT-SPEC.md:3.1`.
- System prompt L1 harus mengandung instruksi: *"Jika user minta seperti [URL/app lain], ekstrak 6-8 fitur inti dari lampiran, lalu WAJIB tanya diferensiasi (target pasar, pricing, fitur tambah/hapus) sebelum generate final. Beri [Asumsi] eksplisit jika belum dijawab."* — ini diedit via menu ini tanpa deploy.
- Test dummy L1 di §9 sudah include `attachment_text` berisi ringkasan MeterPAMS (data pelanggan, area, petugas, tarif progresif/flat, QR, foto meter, print bluetooth, WA).

### 6.2 Handling Khusus — Multi Tenancy di L5 (contoh: "per tenancy DB masing-masing, 4 dashboard admin/petugas/merchant/pelanggan")

- L5 inject `{{step1_final}} + {{step3_final.architecture}}` + `{{user_message}}`. Prompt L5 harus minta AI rekomendasikan pola tenancy (DB per tenant vs shared DB `tenant_id`) dengan pro/kontra, jangan langsung nurut. Detail di `docs/WIZARD-CHAT-SPEC.md:3.2`.
- Output `final_output` L5 wajib ada `tenancy{recommended, opsi[]}, roles[4], entities[], dbml`.

## 7. API & Routes

Prefix `admin` + middleware `role:superadmin`.

| Method | Route | Controller | Deskripsi |
|---|---|---|---|
| GET | `/admin/wizard-prompts` | `WizardPromptController@index` | List 8 step, status aktif, updated_at, preview fallback |
| GET | `/admin/wizard-prompts/{step}` | `show` | JSON detail satu step (untuk modal) |
| PUT | `/admin/wizard-prompts/{step}` | `update` | Simpan + buat version + cache forget + activity log |
| POST | `/admin/wizard-prompts/{step}/test` | `test` | Body: dummy `user_message` opsional (default dummy). Return `preview_rendered`, `ai_raw`, `ai_parsed`, `latency_ms`, `error`. Throttle. |
| GET | `/admin/wizard-prompts/{step}/versions` | `versions` | List versi |
| POST | `/admin/wizard-prompts/{step}/rollback/{version}` | `rollback` | Copy versi lama jadi current (buat versi baru) |

**Payload update:**

```json
{
  "name": "Problem & Vision",
  "system": "Kamu Ideation Partner ...",
  "user_template": "KONTEKS PROJECT:\nJudul: {{title}}\n...",
  "json_schema": "{\"type\":\"object\", ...}",
  "is_active": true
}
```

Validasi: `name: required|string|max:80`, `system: required|string|min:20|max:8000`, `user_template: required|string|min:20|max:12000`, `json_schema: nullable|json`, `is_active: boolean`.

---

## 8. UI — `/admin/wizard-prompts` (Inertia Vue)

**Layout:** pakai `AdminLayout`, header + 8 kartu grid `lg:grid-cols-2`.

**Kartu per step (ringkas):**

- Badge `L1` — `name` — `Aktif/Nonaktif` — `updated_at` — `versi vN`
- Snippet `system` 2 baris + `user_template` 2 baris (monospace)
- Tombol: `Edit` → buka modal, `Test` → inline result, `History` → drawer

**Modal Edit (max-w-3xl):**

- Input `Nama Step` (text)
- Textarea `System Prompt` (8 rows, monospace, counter char)
- Textarea `User Template` (14 rows, monospace) + **chip variabel** di atasnya (klik insert di cursor)
- Input `JSON Schema` (textarea 4 rows, validate JSON live, hint “kosongkan jika tidak perlu validasi”)
- Toggle `Aktif` (switch)
- Panel kanan / bawah: **Live Preview** — render `user_template` dengan dummy data (lihat §6) real-time (computed)
- Tombol: `Batal`, `Test Prompt`, `Simpan` (PUT)

**Test Flow:**

1. Klik `Test Prompt` di modal (atau di kartu) → POST `/test` dengan `user_message` dummy (atau isi dari input kecil “Coba user_message:”)
2. Tampilkan 3 tab: `Rendered Prompt` (system+user), `AI Raw` (content), `Parsed JSON` (pretty) + `latency`.
3. Jika `ai_raw` mengandung `error` → tampilkan merah + hint “Cek API Key / default model”.

**History Drawer:**

- Tabel versi: `v3 — 2026-08-30 14:02 — oleh Tengku — “ubah tone lebih tegas”` — Klik `Lihat` → diff (side-by-side via `diff` sederhana) — `Rollback` → confirm → POST rollback.

**Preview untuk Member (read-only):**

- Di bawah grid, card “Efek ke wizard member” — jelaskan bahwa edit di sini langsung mempengaruhi `/member/projects/{id}/wizard/{step}` setelah cache 60 detik.

---

## 9. Service & Cache

**`App\Services\PromptResolver`**

```php
class PromptResolver {
  public static function get(int $step): array {
    return Cache::remember("wizard_prompt:$step", 60, function() use ($step) {
      $row = WizardPrompt::where('step',$step)->where('is_active',true)->first();
      if ($row) return [
        'name'=>$row->name,
        'system'=>$row->system,
        'user_template'=>$row->user_template,
        'json_schema'=>$row->json_schema,
        'source'=>'db',
        'version'=>$row->updated_at,
      ];
      return config("ai.prompts.$step") + ['source'=>'config'];
    });
  }
  public static function forget(int $step){ Cache::forget("wizard_prompt:$step"); }
}
```

**Ubah `AiService::renderPrompt`** → ambil dari `PromptResolver::get($step)` bukan `config(...)` langsung. Sisanya tetap.

**Dummy data untuk preview & test** (di controller, dipakai juga di Vue preview):

```php
$dummy = [
  'title'=>'Kasir UMKM Offline-First',
  'description'=>'App kasir untuk toko kelontong, owner rekap manual di buku',
  'user_message'=>'Aku mau app bisa cetak struk & rekap harian otomatis',
  'attachment_text'=>'(tidak ada lampiran)',
  'history'=>"USER: mau kasir sederhana\nASSISTANT: oke, targetnya UMKM ...",
  'step1_final'=> json_encode(['problem_statement'=>'...'], JSON_PRETTY_PRINT),
  'step2_final'=> json_encode(['kpis'=>[]], JSON_PRETTY_PRINT),
  'stack'=>'Laravel + Vue + MySQL',
  'industry_or_auto'=>'UMKM / Retail',
];
```

---

## 10. Migrasi & Seeder Plan

1. `2026_08_30_000001_create_wizard_prompts_table.php`
2. `2026_08_30_000002_create_wizard_prompt_versions_table.php`
3. `database/seeders/WizardPromptSeeder.php` — isi 8 row dari `config/ai.php` + v1.

**Langkah deploy (urutan):**

```bash
php artisan make:model WizardPrompt -m
php artisan make:model WizardPromptVersion -m
php artisan make:controller Admin/WizardPromptController --resource
php artisan make:service PromptResolver # manual
php artisan migrate
php artisan db:seed --class=WizardPromptSeeder
php artisan cache:clear
npm run build
```

---

## 11. Validasi & Edge Case

- Jika `wizard_prompts` kosong total → resolver fallback 100% ke config, UI tampil banner “Belum ada prompt di DB — pakai config/ai.php”.
- Jika `is_active=false` untuk step → resolver fallback, kartu step tampil badge `Nonaktif (pakai config)`.
- Jika `json_schema` invalid JSON → tolak save (422), tampil error di bawah textarea.
- Jika `user_template` mengandung `{{unknown_var}}` → tolak save dengan pesan “Variabel {{unknown_var}} tidak dikenal. Pilih dari: title, description, …”.
- Test yang gagal (AI error) tetap tampil `ai_raw` error, tidak block save.
- Rollback: buat versi baru dengan isi versi lama (append, bukan overwrite), jadi history tidak hilang.

---

## 12. Testing

- **Unit:** `PromptResolverTest` — DB ada → return DB, DB tidak ada → return config, cache hit/miss.
- **Feature:** `WizardPromptControllerTest` — superadmin bisa update, admin biasa 403, validasi variabel ditolak, test endpoint throttle, rollback buat versi baru, activity_log tercatat.
- **Manual:** superadmin edit L1 → buka wizard member sebagai `member@buatprd.test` → chat L1 → pastikan prompt baru kepakai (cek `ai_generated` content berubah tone).

---

## 13. Rollout (Fase)

- **Fase 1 (ini):** BE + DB + Resolver + UI admin CRUD + preview + test + versioning. Wizard member belum diubah selain sumber prompt.
- **Fase 2 (next):** prompt per-tier (default vs premium beda prompt), A/B test, prompt marketplace.

---

## 14. File yang Akan Dibuat/Diubah

**Baru:**
- `database/migrations/2026_08_30_000001_create_wizard_prompts_table.php`
- `database/migrations/2026_08_30_000002_create_wizard_prompt_versions_table.php`
- `app/Models/WizardPrompt.php`
- `app/Models/WizardPromptVersion.php`
- `app/Services/PromptResolver.php`
- `app/Http/Controllers/Admin/WizardPromptController.php`
- `resources/js/Pages/Admin/WizardPrompts/Index.vue` (atau `Settings/WizardPrompts.vue`)
- `database/seeders/WizardPromptSeeder.php`

**Ubah:**
- `app/Services/AiService.php` — inject PromptResolver
- `routes/web.php` — tambah 6 route admin wizard-prompts
- `resources/js/Layouts/AdminLayout.vue` — tambah menu “Wizard Prompts” di sidebar (superadmin only)
- `tests/Feature/WizardPromptTest.php` (baru)

**Tidak diubah:** `config/ai.php` (tetap sebagai fallback & seeder source), `AiProvider`, `Setting`.

---

## 15. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Superadmin salah edit prompt → wizard member error (JSON tidak valid) | Validasi `json_schema` + preview + test wajib sebelum save. Versi history + rollback 1 klik. `is_active` toggle untuk fallback instan. |
| Cache stale setelah edit | `PromptResolver::forget()` on update/rollback. |
| Variabel baru belum ada di resolver | Whitelist di `PromptResolver::render()` + pesan error jelas saat save. |
| Test boros kredit AI | Throttle 5/menit + pakai dummy pendek + `max_tokens` kecil di test (800). |

---

## 16. Kriteria Selesai (Definition of Done)

- [ ] 8 row `wizard_prompts` ter-seed dari `config/ai.php`
- [ ] Superadmin bisa buka `/admin/wizard-prompts`, edit L1, save, lihat history, rollback, dan test (melihat raw + parsed)
- [ ] `AiService` sudah baca dari DB (buktikan dengan ubah tone system L1 → generate wizard → hasil berubah)
- [ ] `npm run build` pass, `php artisan test` untuk `WizardPrompt` pass
- [ ] Activity log tercatat, cache invalidate bekerja

---

**Next step jika plan disetujui:** Paijo eksekusi Fase 1 (migrasi + seeder + resolver + controller + Vue) — estimasi 1 sesi. Mau Paijo langsung gas Jo?

<?php

namespace App\Services;

use App\Models\WizardPrompt;
use Illuminate\Support\Facades\Cache;

class PromptResolver
{
    /**
     * Whitelist variabel yang boleh dipakai di user_template.
     * Jika template mengandung var di luar ini -> ditolak saat save (controller), tapi saat render fallback ke "-".
     */
    public const ALLOWED_VARS = [
        'title',
        'description',
        'user_message',
        'attachment_text',
        'history',
        'step1_content',
        'step1_final',
        'step2_final',
        'step3_final',
        'stack',
        'industry_or_auto',
        'industry',
        'project_title',
        'all_steps',
    ];

    /**
     * Alias mapping — biar template bisa pakai {{industry}} atau {{project_title}} tetap ke-resolve.
     */
    public const ALIASES = [
        'industry' => 'industry_or_auto',
        'project_title' => 'title',
    ];

    /**
     * Ambil prompt untuk step tertentu — cache 60 detik.
     * Jika DB ada dan aktif -> pakai DB, else fallback ke config/ai.php.
     *
     * @return array{name:string,system:string,user_template:string|null,json_schema:string|null,source:string,version:mixed}
     */
    public static function get(int $step): array
    {
        return Cache::remember("wizard_prompt:$step", 60, function () use ($step) {
            $row = WizardPrompt::where('step', $step)->where('is_active', true)->first();

            if ($row) {
                return [
                    'name' => $row->name,
                    'system' => $row->system,
                    'user_template' => $row->user_template,
                    'json_schema' => $row->json_schema,
                    'source' => 'db',
                    'version' => $row->updated_at?->toIso8601String(),
                    'id' => $row->id,
                    'step' => $row->step,
                ];
            }

            $cfg = config("ai.prompts.$step");
            if ($cfg) {
                return [
                    'name' => $cfg['name'] ?? "Langkah $step",
                    'system' => $cfg['system'] ?? 'Kamu asisten PRD.',
                    'user_template' => $cfg['user_template'] ?? null,
                    'json_schema' => $cfg['json_schema'] ?? null,
                    'source' => 'config',
                    'version' => 'config',
                    'id' => null,
                    'step' => $step,
                ];
            }

            // Ultimate fallback
            return [
                'name' => "Langkah $step",
                'system' => 'Kamu asisten PRD.',
                'user_template' => null,
                'json_schema' => null,
                'source' => 'config',
                'version' => 'config',
                'id' => null,
                'step' => $step,
            ];
        });
    }

    /**
     * Render user_template dengan vars (dipakai juga untuk preview live di controller tanpa panggil AI).
     */
    public static function render(string $template, array $vars): string
    {
        // Resolve aliases first
        foreach (static::ALIASES as $alias => $target) {
            if (! isset($vars[$alias]) && isset($vars[$target])) {
                $vars[$alias] = $vars[$target];
            }
        }

        $out = $template;
        foreach ($vars as $k => $v) {
            $out = str_replace('{{'.$k.'}}', $v, $out);
        }
        // Aliases second pass (in case template uses alias directly)
        foreach (static::ALIASES as $alias => $target) {
            if (isset($vars[$alias])) {
                $out = str_replace('{{'.$alias.'}}', $vars[$alias], $out);
            }
        }
        // Bersihkan sisa {{unknown}} -> "-"
        $out = preg_replace('/\{\{[^}]+\}\}/', '-', $out);

        return $out;
    }

    /**
     * Validasi variabel di template — return array var tak dikenal (kosong = valid).
     */
    public static function unknownVars(string $template): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $template, $m);
        $found = $m[1] ?? [];
        $unknown = [];
        foreach ($found as $v) {
            if (! in_array($v, static::ALLOWED_VARS, true)) {
                $unknown[] = $v;
            }
        }
        return array_values(array_unique($unknown));
    }

    public static function forget(int $step): void
    {
        Cache::forget("wizard_prompt:$step");
    }

    public static function forgetAll(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            Cache::forget("wizard_prompt:$i");
        }
    }

    /**
     * Dummy data untuk preview & test — konsisten dengan spec §9.
     */
    public static function dummyVars(int $step = 1, ?string $userMessage = null): array
    {
        $attachmentL1 = "Ringkasan MeterPAMS (meterpams.com): aplikasi pencatatan meter air PAM desa. Fitur: data pelanggan (nama, alamat, golongan tarif), area/blok, petugas lapangan, tarif progresif/flat per m3, pencatatan meter bulanan (angka awal-akhir), QR code pelanggan, foto meter sebagai bukti, cetak struk via bluetooth printer, tagihan & tunggakan, pembayaran tunai/transfer, laporan harian/bulanan, notifikasi WA ke pelanggan.";

        return [
            'title' => 'Kasir UMKM Offline-First',
            'description' => 'App kasir untuk toko kelontong, owner rekap manual di buku, butuh cetak struk & rekap harian otomatis',
            'user_message' => $userMessage ?? 'Aku mau app bisa cetak struk & rekap harian otomatis, mirip https://meterpams.com tapi untuk toko kelontong — bedanya target pasar UMKM bukan PAM desa',
            'attachment_text' => $attachmentL1,
            'history' => "USER: mau kasir sederhana untuk toko kelontong\nASSISTANT: oke, targetnya UMKM, offline-first ya?\nUSER: iya, owner sekarang rekap manual di buku",
            'step1_content' => json_encode(['problem_statement' => 'Owner toko kelontong rekap manual di buku, rawan salah hitung & tidak ada laporan harian.', 'target_personas' => [['nama' => 'Ibu Siti', 'deskripsi' => 'Pemilik toko kelontong', 'pain' => 'Rekap manual lama', 'goal' => 'Rekap otomatis']], 'value_proposition' => 'Kasir offline-first yang cetak struk & rekap harian 1 klik'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'step1_final' => json_encode(['problem_statement' => 'Owner toko kelontong rekap manual di buku, rawan salah hitung & tidak ada laporan harian.', 'target_personas' => [['nama' => 'Ibu Siti', 'deskripsi' => 'Pemilik toko kelontong', 'pain' => 'Rekap manual lama', 'goal' => 'Rekap otomatis']], 'value_proposition' => 'Kasir offline-first yang cetak struk & rekap harian 1 klik'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'step2_final' => json_encode(['industri_terdeteksi' => 'Retail / UMKM', 'kpis' => [['nama' => 'Transaksi/hari', 'target' => '50', 'tipe' => 'Leading']], 'north_star' => ['metric' => 'Transaksi berhasil/hari', 'alasan' => 'Inti value']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'step3_final' => json_encode(['architecture' => ['recommended' => 'modular_monolith', 'stack' => ['Laravel', 'Vue']]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'stack' => 'Laravel + Vue + MySQL',
            'industry_or_auto' => 'UMKM / Retail',
            'industry' => 'UMKM / Retail',
            'project_title' => 'Kasir UMKM Offline-First',
            'all_steps' => json_encode(['step1' => '...', 'step2' => '...', 'step3' => '...'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
    }
}

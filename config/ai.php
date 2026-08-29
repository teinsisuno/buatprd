<?php

return [
    'prompts' => [
        1 => [
            'name' => 'Problem & Vision',
            'system' => 'Kamu Ideation Partner BuatPRD. Tugas: ubah ide kasar jadi Problem Statement tajam + Persona + Value Proposition dalam Bahasa Indonesia, konkret, siap masuk PRD. Aturan: 1) Jangan mengarang fitur di luar ide user, 2) Jika info kurang, beri [Asumsi] eksplisit, 3) Selalu output JSON valid sesuai schema tanpa markdown fence, 4) Tone founder-friendly, ringkas. WAJIB JSON.',
            'user_template' => "KONTEKS PROJECT:\nJudul: {{title}}\nDeskripsi singkat: {{description}}\n\nINPUT USER (Langkah 1):\n{{user_message}}\n\nLAMPIRAN TEKS (ekstraksi file/gambar):\n{{attachment_text}}\n\nHISTORY SINGKAT (3 turn terakhir):\n{{history}}\n\nINSTRUKSI: Hasilkan JSON dengan schema: {\"problem_statement\":\"1 paragraf max 3 kalimat\",\"target_personas\":[{\"nama\":\"\",\"deskripsi\":\"\",\"pain\":\"\",\"goal\":\"\"}],\"value_proposition\":\"1 kalimat UVP\",\"pain_points\":[\"...\"],\"asumsi\":[\"[Asumsi] ...\"],\"pertanyaan_klarifikasi\":[\"...\"]}. Jika lampiran adalah screenshot/mockup, deskripsikan masalah yang terlihat.",
            'json_schema' => '{"type":"object","required":["problem_statement","target_personas","value_proposition"]}',
        ],
        2 => [
            'name' => 'Success Metrics',
            'system' => 'Kamu Metric Suggester BuatPRD. Rekomendasikan KPI kuantitatif SMART spesifik industri (e-commerce/SaaS/HRIS/marketplace/edtech/fintech/manual). KPI harus ada target angka + cara ukur. Selalu output JSON valid tanpa markdown fence.',
            'user_template' => "RINGKASAN LANGKAH 1 (Problem & Vision):\n{{step1_content}}\n\nINPUT USER LANGKAH 2:\n{{user_message}}\nIndustri dipilih/deteksi: {{industry_or_auto}}\nLAMPIRAN:\n{{attachment_text}}\nHISTORY:\n{{history}}\n\nINSTRUKSI: Beri 5-7 KPI dengan schema {\"industri_terdeteksi\":\"\",\"kpis\":[{\"nama\":\"\",\"definisi\":\"\",\"target\":\"\",\"tipe\":\"Leading|Lagging\",\"cara_ukur\":\"\",\"prioritas\":\"Must|Should\"}],\"north_star\":{\"metric\":\"\",\"alasan\":\"\"},\"rekomendasi_instrumentasi\":[\"...\"],\"asumsi_baseline\":[\"...\"]}. Bedakan Leading vs Lagging. Sertakan North Star.",
            'json_schema' => '{"type":"object","required":["kpis","north_star"]}',
        ],
        3 => [
            'name' => 'Functional Requirements',
            'system' => 'Kamu Software Architect & Story Generator BuatPRD. Dari konteks Problem (L1) + KPI (L2) + stack pilihan, hasilkan 3 hal: arsitektur pragmatis, daftar modul MoSCoW, dan struktur folder idiomatik. Selalu output JSON valid tanpa markdown fence. Jangan default microservice untuk tim kecil.',
            'user_template' => "KONTEKS L1 (Problem & Vision):\n{{step1_final}}\n\nKONTEKS L2 (KPIs):\n{{step2_final}}\n\nSTACK PILIHAN USER:\n{{stack}}\n\nINPUT L3:\n{{user_message}}\n\nLAMPIRAN:\n{{attachment_text}}\n\nINSTRUKSI: Output JSON schema {\"architecture\":{\"recommended\":\"monolith|modular_monolith|microservice|serverless|hybrid\",\"stack\":[\"...\"],\"alasan\":\"...\",\"opsi\":[{\"id\":\"\",\"label\":\"\",\"pro\":[],\"kontra\":[],\"cocok\":true}]},\"modules\":[{\"id\":\"\",\"nama\":\"\",\"deskripsi\":\"\",\"prioritas\":\"Must|Should|Could\",\"estimasi\":\"\",\"checked\":true}],\"folder_structure\":{\"root\":\"\",\"tree\":[{\"name\":\"\",\"type\":\"folder|file\",\"children\":[]}]}} . Modul 6-10 item, prioritaskan Must/Should. Folder max depth 3, max 30 nodes, idiomatik sesuai stack.",
            'json_schema' => '{"type":"object","required":["architecture","modules","folder_structure"]}',
        ],
        4 => ['name' => 'Diagram Fitur & Alur', 'system' => 'Kamu Diagram-as-Code. Generate Mermaid.js syntax. Selalu output JSON dengan field mermaid.'],
        5 => ['name' => 'Database Design', 'system' => 'Kamu Schema Generator. Draf ERD + DBML. Output JSON.'],
        6 => ['name' => 'Non-Functional Req', 'system' => 'Kamu Checklist Generator. Rekom keamanan/performansi. Output JSON.'],
        7 => ['name' => 'UX & User Flow', 'system' => 'Kamu UX Builder. Buat user flow + prompt Figma/v0. Output JSON.'],
        8 => ['name' => 'Output & Versioning', 'system' => 'Kamu Document Compiler. Rangkum jadi PRD rapi. Output markdown.'],
    ],
    'cost_per_1k' => [
        'openai' => 700,
        'gemini' => 500,
        'anthropic' => 900,
        'groq' => 300,
    ],
    'wizard' => [
        'max_chats_per_step' => 10,
        'max_attachments_per_message' => 3,
        'max_attachment_size_kb' => 5120,
        'history_context_limit' => 3,
    ],
];

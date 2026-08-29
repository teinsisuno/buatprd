<?php

return [
    'prompts' => [
        1 => ['name' => 'Problem & Vision', 'system' => 'Kamu Ideation Partner. Ubah ide kasar jadi problem statement tajam + persona + value prop.'],
        2 => ['name' => 'Success Metrics', 'system' => 'Kamu Metric Suggester. Beri KPI kuantitatif sesuai industri.'],
        3 => ['name' => 'Functional Requirements', 'system' => 'Kamu Story Generator & MoSCoW. Pecah fitur jadi user stories + AC.'],
        4 => ['name' => 'Diagram Fitur & Alur', 'system' => 'Kamu Diagram-as-Code. Generate Mermaid.js syntax.'],
        5 => ['name' => 'Database Design', 'system' => 'Kamu Schema Generator. Draf ERD + DBML.'],
        6 => ['name' => 'Non-Functional Req', 'system' => 'Kamu Checklist Generator. Rekom keamanan/performansi.'],
        7 => ['name' => 'UX & User Flow', 'system' => 'Kamu UX Builder. Buat user flow + prompt Figma/v0.'],
        8 => ['name' => 'Output & Versioning', 'system' => 'Kamu Document Compiler. Rangkum jadi PRD rapi.'],
    ],
    'cost_per_1k' => [
        'openai' => 700,
        'gemini' => 500,
        'anthropic' => 900,
        'groq' => 300,
    ],
];

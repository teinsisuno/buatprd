# Project Overview — BuatPRD

## Overview
BuatPRD adalah SaaS workspace AI untuk mengubah ide kasar menjadi PRD profesional dalam 15 menit via Wizard 8 Langkah terstruktur (Problem → Metrics → Stories → Diagram → DB → NFR → UX → Export). Target: Solo Dev, PM, Founder, Agency.

## Goals
1. Time-to-PRD < 15 menit (ide → PDF jadi)
2. Conversion Free→Paid >12% (tier default sebagai hook)
3. Retensi bulanan >40% via template & kuota
4. Operasional manual rapi tanpa payment gateway di V1

## Core User Flow
1. User daftar → otomatis tier `default` → redirect `/member/dashboard`
2. Buat Project → masuk Wizard Langkah 1-8, tiap langkah bisa Generate AI
3. Kuota habis → upgrade paket → upload bukti transfer → admin approve → tier naik
4. Project selesai → Export PDF/Markdown → share ke tim (premium)
5. Admin kelola user/tier/transaksi/tiket via `/admin/*`

## Features
### Role & Membership
- Roles: superadmin, admin, member (spatie/laravel-permission)
- Tiers: default (gratis), basic (49k), standart (99k), premium (199k) — hybrid subscription + top up kredit
- Manual transfer + upload bukti + approve admin

### Admin Dashboard (/admin)
- Dashboard Overview & Analytics, User & Staff Management, Activity Log
- Paket & Membership Management, Kupon, Transaksi & Invoice, Template PRD, Tiket Support, Setting (Superadmin)

### Member Dashboard (/member)
- Dashboard personal, Project (CRUD + Template + Sampah), Profile, Langganan & Billing, Wallet/Top Up, Usage & Notifikasi, Bantuan Tiket

### PRD Wizard (Inti - 8 Langkah IDEA.md)
- Langkah 1-8 sesuai PRD-BUATPRD-PRODUCTION.md dengan AI per langkah (Ideation, Metric Suggester, MoSCoW, Mermaid, Schema Generator, Checklist, UX Prompt, Document Compiler)

## Scope
### In Scope (Production V1)
- Semua di atas + export PDF/Markdown + Mermaid preview + DBML + tiket + kupon + audit log

### Out of Scope (V2)
- Payment gateway otomatis, OAuth, kolaborasi real-time, public share link, API publik

## Success Criteria
1. Member default bisa buat 2 project & 10x AI, diblokir saat coba export PDF
2. Member upgrade via transfer → admin approve → tier & kuota bertambah dalam <5 menit
3. Wizard 8 langkah bisa dilompati, progress tersimpan, AI generate per langkah berhasil
4. Export PDF/Markdown dari Langkah 8 menghasilkan dokumen rapi
5. Superadmin bisa kelola admin, admin tidak bisa kelola superadmin

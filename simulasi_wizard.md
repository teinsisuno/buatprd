SIMULASI WIZARD PEMBUATAN PRD

## LANGKAH 1 (IDE DARI USER)

- user memberikan ide
- bisa menyertakan gambar, pdf, .md, link

**contoh**
user punya ide : "Aku ingin buat aplikasi management meteran air seperti https://meterpams.com"

## LANGKAH 2 (MENGARAHKAN KE SKELETON PRD)

- Model AI menjawab dalam bentuk checkbox atau pilihan dan selalu menambahkan satu field yang bisa di tulis sendiri oleh user.
- maksimal turn 3 x tanya jawab atau sesuai setting di /admin,
- tombol next muncul bila user tidak memilih [] "tulis pendapat anda sendiri" atau maksimal turn tercapai.

**contoh**
Jawaban AI:

1. User Roles
   [] admin, petugas, merchat/kasir, member
   [] "tulis pendapat anda sendiri"
2. Dashboard :
   [] multi dashboard berdasarkan roles
   [] single dashboard dengan guard
   [] "tulis pendapat anda sendiri"

## LANGKAH 3 FINALISASI

- ai memberikan rangkuman komplit dari step 2.
- ada field perbaikan bila dirasa masih ada yang kurang.

contoh hasil disini adalah draft skeleton dari prd lengkap hingga sampai langkah 3.

## Langkah 4 Membuat

- Ai memberikan diagram dari hasil Langkah 1 sampai langkah 3 dan user dapat menambahkan mengedit diagram tersebut.
- di ui ada 2 panel diagram by Modul dan diagram bay menu
  (karena tidak semua yang menggunakan ini adalah developer jadi masyarakat umum melihat flow berdasarkan menu. dan flow diagram by menu nanti bisa jadi petunjuk penggunaan aplikasi)
- CRUD Canvas template di kedua tab
- edit juga bisa menggunakan chat box agar dibantu ai.

## Langkah 5 Database Design

- AI desain database dari hasil Langkah 4
- user bisa menambahkan dan merubah database canvas atau user bisa mengedit menggunakan chat box agar di bantu ai.

## Langkah 6 Desain UI

## Langkah 7 Non-Functional Requirements

## Langkah 8 Output dan versioning

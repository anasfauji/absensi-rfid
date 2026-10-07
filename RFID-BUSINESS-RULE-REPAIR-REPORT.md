# Laporan Audit dan Perbaikan Business Rule RFID

**Project:** ELIES — Electronic Integrated Education System  
**Tanggal:** 2026-10-06  
**Cakupan:** `backend`

## RFID Business Rule Audit

### Konflik Ditemukan

Tidak ditemukan konflik implementasi yang jelas berupa tap kedua/berikutnya menjadi waktu keluar atau tap terakhir dianggap waktu pulang.

`backend/app/Http/Controllers/Api/RfidGateController.php`, method `tap` (baris 16 dan 119–149): tap valid disimpan sebagai `rfid_event` dengan `hasil_event` `VALID`; tidak ada pemilihan masuk/keluar berdasarkan urutan, dan response hanya mengembalikan `hasil`, identitas/event, serta `waktu_event`. UID tak dikenal juga dicatat dengan `id_kartu_rfid` dan `id_siswa` null (baris 58–68), tanpa membuat `presensi_gate`.

### Tidak Konflik

1. `AttendanceEvaluationService::evaluate` tetap menjadi domain evaluasi kehadiran (`backend/app/Services/AttendanceEvaluationService.php:14–85`); bukti RFID gate diperiksa sebagai salah satu sumber evidence pada method `hasValidGateAttendance` (`:87–94`).
2. Evaluasi Alpa menggunakan cutoff `23:59:59` (`backend/app/Services/AttendanceEvaluationService.php:143–149`), bukan jam pulang normal 15:00.
3. Endpoint `POST /api/rfid/tap` tetap publik sesuai kontrak perangkat saat ini (`backend/routes/api.php:100`); tidak ada autentikasi perangkat baru yang ditambahkan.
4. API tap tidak mengembalikan label `MASUK` atau `KELUAR`, sehingga kontrak response saat ini tidak membentuk pasangan masuk/pulang.

### Perlu Keputusan

1. Skema `presensi_gate` menyediakan `waktu_keluar` dan `sumber_keluar` (`backend/database/migrations/2026_09_07_043226_create_presensi_gate_table.php:24–48`) dan model memuatnya (`backend/app/Models/PresensiGate.php:14–23`). Pencarian pemakaian menemukan kolom tersebut hanya pada definisi schema/model serta fixture test, bukan dalam logika aplikasi. Ini risiko/konflik desain potensial, tetapi belum membuktikan logika RFID lama; perlu keputusan terpisah bila ingin menghapus atau mengubah kolom.
2. Controller saat ini memvalidasi perangkat terdaftar dan aktif (`RfidGateController.php:27–50`). Handover menyebut perangkat dianggap valid untuk tahap ini dan autentikasi perangkat belum dibangun; validasi status/registrasi operasional saat ini dipertahankan, tanpa menambah autentikasi.

### Rekomendasi Perubahan

1. Tidak mengubah implementasi backend pada putaran ini karena tidak ada konflik kode yang jelas untuk diperbaiki tanpa mengubah kontrak atau menebak maksud kolom domain.
2. Pertahankan `waktu_keluar`/`sumber_keluar` sampai ada keputusan eksplisit tentang skema `presensi_gate`.
3. Jika nanti Gate menampilkan waktu event terakhir, labelnya harus “Tap Terakhir” dan nilainya harus bersumber dari event RFID; jangan menyebutnya waktu pulang.

## Perubahan yang Dilakukan

1. File: `RFID-BUSINESS-RULE-REPAIR-REPORT.md`  
   Perubahan: menambahkan laporan audit, temuan, bagian yang tidak konflik, keputusan tertunda, dan rekomendasi.
2. Kode backend: tidak ada perubahan karena audit tidak menemukan konflik implementasi yang memenuhi kriteria perubahan jelas.

## Hal yang Sengaja Tidak Diubah

1. Migration/schema dan kolom `presensi_gate`.
2. HTTP contract endpoint RFID, Sanctum/authorization, pemisahan `rfid_event` dan `presensi_gate`, serta `AttendanceEvaluationService`.
3. Tidak menambahkan test baru dan tidak menjalankan test suite.

## Hal yang Masih Membutuhkan Keputusan

1. Apakah kolom `waktu_keluar` dan `sumber_keluar` akan dipertahankan untuk kebutuhan domain lain atau direncanakan perubahan schema terpisah.
2. Apakah validasi bahwa perangkat telah terdaftar dan berstatus aktif harus tetap menjadi prasyarat penerimaan tap; ini berbeda dari autentikasi perangkat yang belum dibangun.

## Pemeriksaan

Audit statis source code dan pencarian referensi dijalankan. Tidak ada test atau perubahan schema yang dilakukan.

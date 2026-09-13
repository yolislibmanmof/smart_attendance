# ⏰ Smart Attendance — V1.0

Aplikasi absensi web berbasis PHP Native dengan verifikasi multi-faktor:
GPS Geofencing, Wi-Fi/IP Fingerprinting, QR Code, dan Anti-Fake GPS.

## 🛠 Tech Stack
- PHP 7.4+ (Native, PDO, tanpa framework)
- MySQL / MariaDB (`smart_attendance_db`)
- Vanilla JavaScript + library Html5-QRCode
- CSS: 1 file per area (`public.css` & `admin.css`)
- Environment: Laragon / XAMPP

## 🔑 Akun Default
| Role     | Email                     | Password    |
|----------|---------------------------|-------------|
| Admin    | admin@smartattendance.com | password123 |
| Karyawan | budi@smartattendance.com  | password123 |

## 📦 Instalasi
1. Salin folder proyek ke `www/` (Laragon) atau `htdocs/` (XAMPP).
2. Buat database `smart_attendance_db`, import skema tabel (users, shifts, locations, attendances, leaves, settings).
3. Jalankan SQL migrasi: kolom `office_ip`, `clock_in_ip`, `clock_out_ip`, serta isi `qr_token` + `office_ip`.
4. Sesuaikan kredensial di `config/database.php`.
5. Buka `http://localhost/smart_attendance/` → otomatis redirect ke login.

## 🗂 Struktur Folder
- `public/`  → Portal Karyawan (sidebar terang): dashboard, absen, riwayat, cuti
- `admin/`   → Panel Administrator (sidebar gelap): dashboard, users, shifts, locations, leaves, reports
- `api/`     → Endpoint backend: auth, absen_action, leave_action
- `includes/`→ Header/footer layout per area
- `assets/`  → 1 CSS + 1 JS per area
- `uploads/` → Lampiran pengajuan cuti
- `config/`  → Koneksi database PDO

## ✅ Fitur V1.0
### Portal Karyawan
- Login/Register, dashboard statistik pribadi
- Absen Masuk/Pulang dengan verifikasi: Geofencing (Haversine), Wi-Fi/IP kantor, QR Code
- Anti-Fake GPS (deteksi mock location + sanity check akurasi)
- Riwayat kehadiran + filter bulan
- Pengajuan Cuti/Izin/Sakit + lampiran + pantau status

### Panel Administrator
- Dashboard manajerial (hadir/terlambat/cuti pending hari ini)
- Master data karyawan (tambah, assign shift, reset password, nonaktifkan)
- CRUD Shift & toleransi terlambat
- Manajemen Lokasi: koordinat, radius, Wi-Fi, IP kantor, generate & regenerate QR
- Approval cuti (setuju/tolak + catatan)
- Laporan bulanan: rekap per karyawan, detail, tingkat kehadiran, Export CSV

### Mesin Aturan HR
- Status Hadir/Terlambat otomatis dari shift + toleransi
- Karyawan dengan cuti disetujui otomatis diblokir absen
- 1 record absensi per user per hari (unique key)
- Role guard: admin & karyawan terpisah total (layout, akses, logout)

## 🚀 Roadmap V2 (Next Level)
1. QR Code berputar (token regenerasi tiap 60 detik via JS) — anti foto QR
2. Face Recognition + Liveness Detection saat absen
3. PWA + Offline Mode (antrean absen saat blank spot)
4. Bot WhatsApp/Telegram: reminder lupa absen & notifikasi approval
5. Dynamic Geofencing untuk karyawan lapangan/client visit
6. Analitik AI: deteksi anomali pola absen & risiko burnout
7. Gamification: badge & poin kehadiran
8. Export payroll & laporan PDF otomatis
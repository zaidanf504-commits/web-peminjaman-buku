# 📚 Digital Library - Panduan Setup & Penggunaan

## 🚀 Fitur Utama Sistem

### **Untuk User/Mahasiswa:**
- ✅ Login & Register akun
- ✅ Melihat katalog buku yang tersedia
- ✅ Pencarian & filter buku berdasarkan kategori
- ✅ Peminjaman buku (maksimal 3 buku per saat)
- ✅ Melihat koleksi pribadi (buku yang sedang dipinjam)
- ✅ Melihat riwayat peminjaman & pengembalian

### **Untuk Admin:**
- ✅ Kelola katalog buku (tambah, hapus)
- ✅ Upload cover buku
- ✅ Monitor peminjaman aktif
- ✅ Konfirmasi pengembalian buku
- ✅ Kelola akun user

---

## 📋 Langkah Setup Sistem

### **1. Persiapan Database**

#### Buka phpMyAdmin:
```
http://localhost/phpmyadmin
```

#### Buat database baru:
- **Database name:** `digitallibrary`
- **Collation:** `utf8mb4_general_ci`

#### Import SQL Schema:
1. Pilih database `digitallibrary`
2. Tab **"Import"**
3. Upload file: `DATABASE_SCHEMA.sql` (dari folder project)
4. Klik **"Import"**

#### Jika import gagal, jalankan manual:
```sql
-- Copy & paste semua query dari DATABASE_SCHEMA.sql ke tab SQL
-- kemudian jalankan
```

#### Verifikasi Tabel:
```sql
SHOW TABLES;
-- Harusnya ada: user, buku, peminjaman, ulasan
```

#### Pastikan kolom 'Role' ada di tabel user:
```sql
ALTER TABLE user ADD COLUMN Role VARCHAR(20) DEFAULT 'user' AFTER Username;
```

#### Set admin role (ubah sesuai UserID):
```sql
UPDATE user SET Role = 'admin' WHERE UserID = 1;
UPDATE user SET Role = 'user' WHERE Role IS NULL OR Role = '';
```

---

### **2. Struktur Folder Project**

```
Web Peminjaman/
├── index.php                      (Dashboard user)
├── login.php                      (Login)
├── register.php                   (Register)
├── logout.php                     (Logout)
├── cari_buku.php                  (Katalog & peminjaman)
├── koleksi.php                    (Koleksi pribadi)
├── pengembalian.php               (Riwayat peminjaman)
├── admin_peminjaman.php           (Admin panel)
├── proses_pinjam.php              (Proses peminjaman)
├── proses_kembali.php             (Proses pengembalian)
├── koneksi.php                    (Konfigurasi database)
├── DATABASE_SCHEMA.sql            (SQL schema)
├── README.md                      (File ini)
├── uploads/                       (Folder untuk cover buku)
├── image/                         (Folder gambar aset)
├── logo/                          (Folder logo)
└── ...
```

---

### **3. Setup File Konfigurasi**

#### Buka `koneksi.php`:
Pastikan konfigurasi database sudah benar:

```php
<?php
$host = "localhost";
$user = "root";           // Username MySQL
$pass = "";              // Password MySQL (default kosong)
$db   = "digitallibrary";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
```

---

### **4. Setup Folder Uploads**

Pastikan folder `uploads/` sudah ada dengan permission **755**:

```bash
# Di command line / terminal
mkdir uploads
chmod 755 uploads
```

Folder ini untuk menyimpan cover buku yang diupload admin.

---

## 📖 Panduan Penggunaan

### **A. Sebagai USER/MAHASISWA:**

#### 1️⃣ **Daftar Akun Baru**
- Buka: `http://localhost/Web%20Peminjaman/register.php`
- Isi form dengan data lengkap
- Klik **"Register"**
- Akan otomatis redirect ke login

#### 2️⃣ **Login**
- Buka: `http://localhost/Web%20Peminjaman/login.php`
- Masukkan **Username** & **Password**
- Klik **"Sign In"**
- Akan masuk ke **Dashboard Utama**

#### 3️⃣ **Mencari & Meminjam Buku**
- Menu: **"Cari & Pinjam Buku"**
- Gunakan fitur pencarian untuk mencari judul/penulis
- Filter berdasarkan kategori
- Klik **"Pinjam Sekarang"** untuk buku yang tersedia
- Buku akan masuk ke **Keranjang Peminjaman**
- Klik **"Lanjut Konfirmasi Pinjam"** untuk selesai
- Durasi peminjaman: **14 hari**

#### 4️⃣ **Lihat Koleksi Pribadi**
- Menu: **"Koleksi Pribadi"**
- Lihat semua buku yang sedang dipinjam
- Lihat sisa waktu pengembalian

#### 5️⃣ **Riwayat Peminjaman & Pengembalian**
- Menu: **"Pengembalian & Ulasan"**
- Lihat riwayat peminjaman (sedang dipinjam & sudah dikembalikan)
- Monitor tanggal kembali
- Fitur ulasan (akan dikembangkan)

---

### **B. Sebagai ADMIN:**

#### 1️⃣ **Login Admin**
- Gunakan akun yang sudah set Role = 'admin'
- Login di: `http://localhost/Web%20Peminjaman/login.php`
- Akan masuk ke **Admin Control Panel**

#### 2️⃣ **Tambah Buku Baru**
- Menu: **"Kelola Buku"**
- Isi form:
  - **Judul Buku** (required)
  - **Penulis** (required)
  - **Penerbit**
  - **Kategori** (required)
  - **ISBN**
  - **Tahun Terbit**
  - **Stok** (default: 1)
  - **Cover** (upload gambar JPG/PNG)
- Klik **"Tambah Buku"**
- Buku akan otomatis tampil di halaman user

#### 3️⃣ **Lihat Daftar Buku**
- Menu: **"Kelola Buku"** → **Bagian Kanan**
- Lihat semua buku yang sudah ditambahkan
- Lihat stok terkini
- Tombol **"Hapus"** untuk menghapus buku

#### 4️⃣ **Monitor Peminjaman**
- Menu: **"Kelola Peminjaman"**
- Lihat semua user yang sedang meminjam
- Lihat detail peminjaman (nama, buku, tanggal)
- Klik **"Konfirmasi Kembali"** ketika user mengembalikan buku
- Stok buku akan otomatis bertambah

#### 5️⃣ **Kelola Data User**
- Menu: **"Kontrol Data User"**
- Lihat semua akun user terdaftar
- Lihat role setiap user
- Tombol **"Hapus User"** untuk menghapus akun

---

## 🔄 Alur Sistem Peminjaman

```
USER LOGIN
    ↓
DASHBOARD (Lihat statistik buku)
    ↓
CARI & PINJAM BUKU (Filter & pencarian)
    ↓
TAMBAH KE KERANJANG (Max 3 buku)
    ↓
KONFIRMASI PEMINJAMAN
    ↓
DATA DISIMPAN KE DATABASE
    ↓
STOK BUKU BERKURANG
    ↓
USER LIHAT KOLEKSI PRIBADI
    ↓
[14 HARI PEMINJAMAN]
    ↓
LIHAT RIWAYAT & PENGEMBALIAN
    ↓
ADMIN KONFIRMASI PENGEMBALIAN
    ↓
STOK BUKU BERTAMBAH
    ↓
PEMINJAMAN SELESAI
```

---

## 🔐 Sistem Role & Keamanan

### **Role System:**
- **User (default):** Hanya bisa pinjam buku, lihat katalog
- **Admin:** Kelola buku, monitor peminjaman, kelola user

### **Keamanan:**
- Password di-hash dengan `password_hash()`
- SQL prepared statements (ada improvements yang perlu dilakukan)
- Session-based authentication
- CSRF protection pada form (akan ditambahkan)

---

## 📊 Tabel Database

### **Tabel: user**
```
UserID (PK) | Username | Password (hash) | Email | NamaLengkap | Role
1           | admin    | bcrypt_hash     | admin@... | Admin | admin
2           | john     | bcrypt_hash     | john@...  | John Doe | user
```

### **Tabel: buku**
```
BukuID (PK) | Judul | Penulis | Penerbit | Kategori | Stok | ISBN | TahunTerbit | Cover
1           | Clean Code | Robert Martin | ... | Teknologi | 3 | ... | 2008 | cover_1.jpg
```

### **Tabel: peminjaman**
```
PeminjamanID (PK) | UserID (FK) | BukuID (FK) | TanggalPeminjaman | TanggalKembaliEstimasi | TanggalPengembalian | Status
1                 | 2           | 1           | 2024-01-01        | 2024-01-15             | NULL                | Dipinjam
```

---

## 🐛 Troubleshooting

### **Error: "Koneksi database gagal"**
- Pastikan MySQL/MariaDB service berjalan
- Cek username & password di `koneksi.php`
- Pastikan database `digitallibrary` sudah dibuat

### **Error: "Table doesn't exist"**
- Import `DATABASE_SCHEMA.sql` ke database
- Atau jalankan query manual di phpMyAdmin

### **File upload cover tidak berfungsi**
- Pastikan folder `uploads/` sudah ada
- Set permission folder ke `755` atau `777`
- Cek size gambar (max 5MB)

### **Login tidak berhasil**
- Pastikan user sudah register terlebih dahulu
- Pastikan password benar
- Cek browser console untuk error messages

### **User masuk ke halaman admin**
- Pastikan Role di database sudah di-set dengan benar
- Update query: `UPDATE user SET Role = 'admin' WHERE UserID = 1;`

---

## 📝 Fitur yang Bisa Dikembangkan

- [ ] Fitur perpanjangan peminjaman
- [ ] Sistem rating & review buku
- [ ] Notifikasi email untuk pengembalian
- [ ] Reservasi buku yang sedang dipinjam
- [ ] Export laporan peminjaman ke PDF
- [ ] Membership levels & point system
- [ ] Kolaborasi dengan e-book provider
- [ ] Mobile app version

---

## 👨‍💻 Support & Contact

Untuk pertanyaan atau bug reports:
- Email: support@digitallibrary.local
- Dokumentasi: Lihat file ini

---

**Terima kasih telah menggunakan Digital Library! 📚**

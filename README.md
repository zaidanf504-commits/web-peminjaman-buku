<div align="center">

# 📚 Digital Library

**Sistem Peminjaman Buku Perpustakaan Berbasis Web**

Cari buku. Pinjam. Kembalikan. Beri ulasan. Semua dari satu tempat.

</div>

---

## 📌 Tentang Web Ini

**Digital Library** adalah sistem perpustakaan digital yang menghubungkan **pengguna** dengan **koleksi buku fisik perpustakaan**. Pengguna tidak perlu lagi datang ke loket untuk meminjam — cukup buka web, cari buku, dan ajukan peminjaman secara online.

Sistem ini terdiri dari **dua peran**:

| | Peran | Tugas Utama |
|---|---|---|
| 👤 | **User** | Mencari buku, meminjam, mengembalikan, memberi ulasan |
| 🔑 | **Admin** | Mengelola katalog buku, mengonfirmasi pengembalian, mengelola user |

---

## 👤 Cara Kerja User

User adalah anggota perpustakaan. Berikut aktivitas yang bisa dilakukan:

### 🔍 1. Mencari Buku

User bisa menelusuri katalog berdasarkan:
- **Judul** — `Clean Code`, `Atomic Habits`, `Filosofi Teras`
- **Penulis** — `Robert C. Martin`, `James Clear`
- **Penerbit** atau **ISBN**
- **Kategori** — Teknologi, Sastra, Filsafat, Bisnis, dll.

Tersedia juga filter **stok tersedia / habis** dan urutan **A–Z** atau **stok terbanyak**.

### 🛒 2. Meminjam Buku

User dapat meminjam hingga **3 buku** sekaligus:

```
Pilih buku → Tambah ke keranjang → Konfirmasi pinjam
```

Setelah dikonfirmasi:
- Buku masuk ke **Koleksi Pribadi**
- Durasi pinjam **14 hari** sejak tanggal peminjaman
- Stok buku otomatis berkurang 1

### 📚 3. Melihat Koleksi Pribadi

Di halaman **Koleksi Pribadi**, user bisa:
- Lihat semua buku yang sedang dipinjam
- Lihat **sisa hari** sebelum jatuh tempo
- Lihat badge merah jika sudah mendekati / melewati tenggat

### 🔄 4. Mengembalikan Buku

Setelah selesai membaca:

```
Klik "Ajukan Pengembalian" → Status berubah → Tunggu konfirmasi admin
```

Status akan berubah menjadi **"Menunggu Konfirmasi"**. Setelah admin menyetujui, buku dianggap sudah dikembalikan dan stok otomatis bertambah.

### ⭐ 5. Memberi Ulasan

Setelah buku dikonfirmasi kembali oleh admin, user bisa:
- Memberi **rating 1–5 bintang**
- Menulis **komentar** pengalaman membaca

Ulasan ini membantu pembaca lain memilih buku.

---

## 🔑 Cara Kerja Admin

Admin adalah pengelola perpustakaan. Admin memiliki 3 area kerja:

### 📕 1. Kelola Katalog Buku

Admin dapat mengelola seluruh koleksi buku:

| Aksi | Yang Dilakukan |
|---|---|
| ➕ **Tambah Buku** | Isi judul, penulis, penerbit, kategori, stok, ISBN, tahun, dan upload cover |
| ✏️ **Edit Buku** | Perbarui informasi buku, ganti cover jika perlu |
| 🗑️ **Hapus Buku** | Hapus buku dari katalog beserta file covernya |

### 🔄 2. Konfirmasi Pengembalian

Admin melihat daftar peminjaman aktif, diprioritaskan yang statusnya **"Menunggu Konfirmasi"**. Saat user mengembalikan buku fisik, admin klik **"Terima Pengembalian"**.

Sistem otomatis:
- ✅ Mencatat tanggal pengembalian aktual
- ✅ Mengubah status menjadi **"Dikembalikan"**
- ✅ Menambah stok buku kembali (+1)

### 👥 3. Kontrol User

Admin dapat:
- Lihat **semua akun** yang terdaftar
- Lihat role masing-masing (`admin` / `user`)
- **Hapus user** jika diperlukan

> Admin **tidak bisa menghapus akunnya sendiri** — sistem otomatis menolak.

---

## 🔄 Bagaimana Sistem Bekerja

### Alur Peminjaman

```
User cari buku
      │
      ▼
Tambah ke keranjang
      │
      ▼
Sistem cek:
  ├─ Kuota user < 3?      ─── Tidak → Tolak
  └─ Stok buku > 0?       ─── Tidak → Tolak
      │
      ▼
Sistem catat:
  ├─ Tanggal pinjam       = hari ini
  ├─ Tanggal kembali      = hari ini + 14
  └─ Status               = "Dipinjam"
      │
      ▼
Stok buku -1
      │
      ▼
Buku muncul di Koleksi Pribadi user
```

### Alur Pengembalian

```
User klik "Ajukan Pengembalian"
      │
      ▼
Status = "Menunggu Konfirmasi"
      │
      ▼
Admin terima pengembalian
      │
      ▼
Sistem catat:
  ├─ Tanggal pengembalian = hari ini
  └─ Status               = "Dikembalikan"
      │
      ▼
Stok buku +1
      │
      ▼
User bisa memberi ulasan
```

### Aturan Sistem

| Aturan | Nilai |
|---|---|
| Maksimal buku per user | **3 buku** |
| Durasi peminjaman | **14 hari** |
| Batas peminjaman per hari | **10 kali** |
| Rating ulasan | **1–5 bintang** |
| Ulasan per peminjaman | **1 kali** (setelah dikembalikan) |

---

## 🗂 Fungsi Setiap File

Setiap file PHP memiliki tugas spesifik:

### Halaman untuk User

| File | Fungsi |
|---|---|
| `landing.php` | Halaman awal sebelum login — menampilkan katalog preview & info |
| `login.php` | Halaman masuk ke akun |
| `register.php` | Halaman pendaftaran akun baru |
| `index.php` | Dashboard user — ringkasan aktivitas & koleksi terbaru |
| `cari_buku.php` | Katalog buku — pencarian, filter, keranjang peminjaman |
| `koleksi.php` | Daftar buku yang sedang dipinjam user |
| `pengembalian.php` | Riwayat peminjaman + tab ulasan |

### Halaman untuk Admin

| File | Fungsi |
|---|---|
| `admin_peminjaman.php` | Panel admin — kelola buku, peminjaman, dan user |

### Proses Backend (tidak punya tampilan)

| File | Fungsi |
|---|---|
| `proses_pinjam.php` | Memproses peminjaman dari keranjang ke database |
| `proses_kembali.php` | Memproses konfirmasi pengembalian oleh admin |
| `proses_ulasan.php` | Menyimpan ulasan & rating user |
| `ajukan_pengembalian.php` | Mengubah status peminjaman menjadi "Menunggu Konfirmasi" |
| `logout.php` | Menghapus session & kembali ke login |

### File Sistem

| File | Fungsi |
|---|---|
| `koneksi.php` | Koneksi ke database MySQL |
| `DATABASE_SCHEMA.sql` | Struktur tabel database |

---

## 🗄 Struktur Data

Sistem ini menyimpan data dalam **4 tabel**:

```
┌──────────────┐        ┌──────────────┐        ┌──────────────┐
│    user      │        │    buku      │        │  peminjaman  │
├──────────────┤        ├──────────────┤        ├──────────────┤
│ UserID       │◄───┐   │ BukuID       │◄───┐   │ PeminjamanID │
│ Username     │    │   │ Judul        │    │   │ UserID       │
│ Password     │    │   │ Penulis      │    │   │ BukuID       │
│ NamaLengkap  │    │   │ Penerbit     │    │   │ TglPinjam    │
│ Role         │    │   │ Kategori     │    │   │ TglKembali   │
└──────────────┘    │   │ Stok         │    │   │ TglDikembali │
                    │   │ Cover        │    │   │ Status       │
                    │   └──────────────┘    │   └──────────────┘
                    │                       │          ▲
                    │                       │          │
                    │   ┌──────────────┐    │          │
                    │   │   ulasan     │    │          │
                    │   ├──────────────┤    │          │
                    └───│ UserID       │    │          │
                        │ BukuID       │────┘          │
                        │ PeminjamanID │───────────────┘
                        │ Rating       │
                        │ Komentar     │
                        └──────────────┘
```

- **user** — menyimpan akun pengguna & admin
- **buku** — menyimpan katalog buku perpustakaan
- **peminjaman** — mencatat setiap transaksi peminjaman
- **ulasan** — menyimpan rating & komentar user

---

## 🎯 Ringkasan Singkat

| | User | Admin |
|---|---|---|
| **Login** | ✅ | ✅ |
| **Cari buku** | ✅ | ✅ (kelola) |
| **Pinjam buku** | ✅ | ❌ |
| **Kembalikan buku** | Ajukan | Konfirmasi |
| **Beri ulasan** | ✅ | ❌ |
| **Tambah/edit/hapus buku** | ❌ | ✅ |
| **Kelola user** | ❌ | ✅ |

---

<div align="center">

**Digital Library** — dibuat untuk mempermudah akses literasi 📚

</div>

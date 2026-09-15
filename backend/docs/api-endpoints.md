# API Endpoints

Dokumentasi endpoint API untuk aplikasi Absensi RFID.

## Konvensi Umum

- Base URL lokal: `http://127.0.0.1:8000/api`
- Semua response API menggunakan format JSON.
- Gunakan header `Accept: application/json` pada setiap request.
- Endpoint yang membutuhkan login menggunakan Laravel Sanctum dengan Bearer Token.
- `id_pengguna` menggunakan tipe integer.
- Pagination belum digunakan pada endpoint yang tersedia saat ini.

Header umum:

```http
Accept: application/json
```

Header untuk endpoint yang membutuhkan login:

```http
Authorization: Bearer <token>
Accept: application/json
```

Tambahkan header berikut untuk request yang memiliki body JSON, seperti `POST` dan `PUT`:

```http
Content-Type: application/json
```

## Ringkasan Endpoint

| Method | Path | Akses | Deskripsi |
| --- | --- | --- | --- |
| `POST` | `/login` | Publik | Login dan membuat token akses |
| `POST` | `/logout` | Semua pengguna login | Logout dan menghapus token aktif |
| `GET` | `/me` | Semua pengguna login | Mengambil data pengguna yang sedang login |
| `GET` | `/pengguna` | `ADMIN` | Mengambil daftar pengguna |
| `POST` | `/pengguna` | `ADMIN` | Membuat pengguna baru |
| `GET` | `/pengguna/{id_pengguna}` | `ADMIN` | Mengambil detail pengguna |
| `PUT` | `/pengguna/{id_pengguna}` | `ADMIN` | Memperbarui pengguna |
| `DELETE` | `/pengguna/{id_pengguna}` | `ADMIN` | Menghapus pengguna |

Path pada tabel di atas relatif terhadap `/api`.

Contoh:

```text
POST /api/login
GET /api/pengguna
```

## Format Error Umum

### 401 Unauthorized

Terjadi jika request membutuhkan token, tetapi token tidak ada, salah, atau sudah logout.

```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden

Terjadi jika pengguna sudah login, tetapi role tidak memiliki akses.

```json
{
  "message": "Akses ditolak."
}
```

### 404 Not Found

Terjadi jika data yang dicari tidak ditemukan.

```json
{
  "message": "Pengguna tidak ditemukan."
}
```

### 422 Unprocessable Content

Terjadi jika validasi gagal.

```json
{
  "message": "The username has already been taken.",
  "errors": {
    "username": [
      "The username has already been taken."
    ]
  }
}
```

## Authentication

### POST /api/login

Login pengguna dan menghasilkan token Sanctum.

Akses: Publik

Request:

```json
{
  "username": "admin",
  "password": "Admin123!"
}
```

Response sukses: `200 OK`

```json
{
  "message": "Login berhasil.",
  "data": {
    "pengguna": {
      "id_pengguna": 1,
      "username": "admin",
      "role": "ADMIN",
      "nama_tampilan": "Administrator",
      "email": null,
      "status": "AKTIF"
    },
    "token": "1|example-token"
  }
}
```

Kemungkinan error:

| Status | Kondisi |
| --- | --- |
| `401` | Username atau password salah |
| `403` | Status akun bukan `AKTIF` |
| `422` | Field `username` atau `password` tidak valid |

### POST /api/logout

Logout pengguna dengan menghapus token yang sedang digunakan.

Akses: Semua pengguna login

Header:

```http
Authorization: Bearer <token>
Accept: application/json
```

Response sukses: `200 OK`

```json
{
  "message": "Logout berhasil."
}
```

Setelah logout berhasil, token yang sama tidak bisa digunakan lagi.

### GET /api/me

Mengambil data pengguna yang sedang login berdasarkan token aktif.

Akses: Semua pengguna login

Header:

```http
Authorization: Bearer <token>
Accept: application/json
```

Response sukses: `200 OK`

```json
{
  "message": "Token valid.",
  "data": {
    "pengguna": {
      "id_pengguna": 1,
      "username": "admin",
      "role": "ADMIN",
      "nama_tampilan": "Administrator",
      "email": null,
      "status": "AKTIF"
    }
  }
}
```

## Pengguna

Semua endpoint pada bagian ini hanya dapat diakses oleh pengguna dengan role `ADMIN`.

Header:

```http
Authorization: Bearer <token-admin>
Accept: application/json
```

### GET /api/pengguna

Mengambil daftar pengguna.

Response sukses: `200 OK`

```json
{
  "message": "Daftar pengguna berhasil diambil.",
  "data": {
    "pengguna": [
      {
        "id_pengguna": 1,
        "username": "admin",
        "role": "ADMIN",
        "nama_tampilan": "Administrator",
        "email": null,
        "status": "AKTIF"
      },
      {
        "id_pengguna": 2,
        "username": "siswa",
        "role": "SISWA",
        "nama_tampilan": "Siswa Test",
        "email": null,
        "status": "AKTIF"
      }
    ]
  }
}
```

### GET /api/pengguna/{id_pengguna}

Mengambil detail satu pengguna berdasarkan `id_pengguna`.

Response sukses: `200 OK`

```json
{
  "message": "Detail pengguna berhasil diambil.",
  "data": {
    "pengguna": {
      "id_pengguna": 1,
      "username": "admin",
      "role": "ADMIN",
      "nama_tampilan": "Administrator",
      "email": null,
      "status": "AKTIF"
    }
  }
}
```

Jika pengguna tidak ditemukan: `404 Not Found`

```json
{
  "message": "Pengguna tidak ditemukan."
}
```

### POST /api/pengguna

Membuat pengguna baru.

Header tambahan:

```http
Content-Type: application/json
```

Request:

```json
{
  "username": "guru",
  "password": "password",
  "role": "GURU",
  "nama_tampilan": "Guru Test",
  "email": null,
  "status": "AKTIF"
}
```

Aturan field:

| Field | Wajib | Aturan |
| --- | --- | --- |
| `username` | Ya | String, maksimal 100 karakter, unik |
| `password` | Ya | String, minimal 6 karakter |
| `role` | Ya | `ADMIN`, `GURU`, atau `SISWA` |
| `nama_tampilan` | Ya | String, maksimal 150 karakter |
| `email` | Tidak | Email valid, maksimal 150 karakter, unik jika diisi |
| `status` | Ya | `AKTIF` atau `NONAKTIF` |

Response sukses: `201 Created`

```json
{
  "message": "Pengguna berhasil dibuat.",
  "data": {
    "pengguna": {
      "id_pengguna": 3,
      "username": "guru",
      "role": "GURU",
      "nama_tampilan": "Guru Test",
      "email": null,
      "status": "AKTIF"
    }
  }
}
```

### PUT /api/pengguna/{id_pengguna}

Memperbarui data pengguna.

Header tambahan:

```http
Content-Type: application/json
```

Field `password` bersifat opsional. Kirim `password` hanya jika ingin mengubah password.

Request tanpa mengubah password:

```json
{
  "username": "guru",
  "role": "GURU",
  "nama_tampilan": "Guru Test Update",
  "email": null,
  "status": "AKTIF"
}
```

Request dengan mengubah password:

```json
{
  "username": "guru",
  "password": "password-baru",
  "role": "GURU",
  "nama_tampilan": "Guru Test Update",
  "email": null,
  "status": "AKTIF"
}
```

Response sukses: `200 OK`

```json
{
  "message": "Pengguna berhasil diperbarui.",
  "data": {
    "pengguna": {
      "id_pengguna": 3,
      "username": "guru",
      "role": "GURU",
      "nama_tampilan": "Guru Test Update",
      "email": null,
      "status": "AKTIF"
    }
  }
}
```

Jika pengguna tidak ditemukan: `404 Not Found`

```json
{
  "message": "Pengguna tidak ditemukan."
}
```

### DELETE /api/pengguna/{id_pengguna}

Menghapus pengguna.

Response sukses: `200 OK`

```json
{
  "message": "Pengguna berhasil dihapus."
}
```

Jika pengguna tidak ditemukan: `404 Not Found`

```json
{
  "message": "Pengguna tidak ditemukan."
}
```

Jika admin mencoba menghapus akun sendiri: `422 Unprocessable Content`

```json
{
  "message": "Tidak dapat menghapus akun sendiri."
}
```

## Role dan Akses

Role yang tersedia saat ini:

| Role | Keterangan |
| --- | --- |
| `ADMIN` | Pengelola sistem dan pengguna |
| `GURU` | Pengguna guru |
| `SISWA` | Pengguna siswa |

Aturan akses yang sudah berjalan:

| Kondisi | Hasil |
| --- | --- |
| Tidak ada token | `401 Unauthorized` |
| Token valid, role sesuai | Request berhasil |
| Token valid, role tidak sesuai | `403 Forbidden` |
| Token sudah logout | `401 Unauthorized` |

## Endpoint Pengujian Internal

Endpoint berikut hanya untuk pengujian middleware. Endpoint ini belum menjadi kontrak API utama dan dapat dihapus saat modul asli sudah lengkap.

| Method | Path | Akses | Response |
| --- | --- | --- | --- |
| `GET` | `/admin/check` | `ADMIN` | `{ "message": "Akses admin berhasil." }` |
| `GET` | `/multi-role/check` | `ADMIN` atau `SISWA` | `{ "message": "Akses multi-role berhasil." }` |

Contoh path lengkap:

```text
GET /api/admin/check
GET /api/multi-role/check
```
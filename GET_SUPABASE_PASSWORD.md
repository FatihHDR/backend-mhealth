# 🔑 Cara Mendapatkan Database Password Supabase

## Project Anda: `ugprcbrcdrbkxjitypne`

---

## 📍 **Langkah 1: Buka Database Settings**

Klik link ini (akan otomatis buka halaman yang benar):
```
https://app.supabase.com/project/ugprcbrcdrbkxjitypne/settings/database
```

Atau manual:
1. Buka https://app.supabase.com
2. Login dengan akun Anda
3. Pilih project **ugprcbrcdrbkxjitypne**
4. Klik **Settings** (⚙️) di sidebar kiri bawah
5. Klik **Database**

---

## 📋 **Langkah 2: Lihat Connection Info**

Scroll ke bawah sampai menemukan section **"Connection Info"** atau **"Connection parameters"**

Anda akan melihat:

```
┌─────────────────────────────────────────────┐
│ Connection Info                             │
├─────────────────────────────────────────────┤
│                                             │
│ Host                                        │
│ db.ugprcbrcdrbkxjitypne.supabase.co       │
│                                             │
│ Database name                               │
│ postgres                                    │
│                                             │
│ Port                                        │
│ 5432                                        │
│                                             │
│ User                                        │
│ postgres                                    │
│                                             │
│ Password                                    │
│ [your password here - might be hidden]      │
│                                             │
└─────────────────────────────────────────────┘
```

---

## 🔐 **Langkah 3A: Jika Password Terlihat**

Jika password terlihat:
1. **Copy** password tersebut
2. Paste ke `.env` di bagian `DB_PASSWORD=`

---

## 🔄 **Langkah 3B: Jika Lupa/Tidak Tahu Password**

Jika password tidak terlihat atau lupa:

1. Di halaman yang sama, cari tombol **"Reset Database Password"** atau **"Generate new password"**
2. Klik tombol tersebut
3. Anda akan diminta konfirmasi - **Klik "I understand"** dan **"Reset Password"**
4. Password baru akan muncul - **COPY SEGERA!** (tidak akan ditampilkan lagi)
5. Paste ke `.env` di `DB_PASSWORD=`

⚠️ **PENTING**: 
- Password hanya ditampilkan SEKALI saat reset
- Simpan di tempat aman (password manager)
- Setelah reset, aplikasi lain yang pakai DB ini perlu update password juga

---

## 📝 **Langkah 4: Update File .env**

Edit file `.env` Anda:

```env
# SEBELUM (yang sekarang):
DB_PASSWORD=YOUR_DATABASE_PASSWORD_HERE

# SESUDAH (ganti dengan password asli):
DB_PASSWORD=paste-password-disini-tanpa-tanda-kutip
```

**Contoh** (password palsu):
```env
DB_PASSWORD=abc123XYZ789password
```

---

## ✅ **Langkah 5: Test Koneksi**

Setelah update password, test koneksi:

```bash
# Clear cache
php artisan config:clear

# Test koneksi
php artisan migrate:status
```

**Jika BERHASIL**, Anda akan melihat:
```
Migration name ......................... Batch / Status
0001_01_01_000000_create_users_table ... Pending
...
```

**Jika GAGAL** (error password):
```
Access denied for user 'postgres'
```
→ Berarti password salah, ulangi dari langkah 3B

---

## 🎯 **Connection String Alternative**

Jika masih bingung, coba cara ini:

1. Di halaman Database Settings
2. Cari section **"Connection String"** atau **"URI"**
3. Pilih mode **"URI"**
4. Anda akan lihat string seperti ini:

```
postgresql://postgres.ugprcbrcdrbkxjitypne:[YOUR-PASSWORD]@db.ugprcbrcdrbkxjitypne.supabase.co:5432/postgres
```

5. Password ada di antara `:` dan `@`
   ```
   postgres.ugprcbrcdrbkxjitypne:[PASSWORD_DISINI]@db.ugp...
   ```

6. Copy password dari situ

---

## 📞 **Butuh Bantuan?**

Jika masih gagal:

### Option 1: Reset Password
- Halaman Database Settings → Reset Database Password
- Buat password baru yang mudah diingat

### Option 2: Contact Supabase
- Dashboard → Support → New Support Ticket
- Atau: https://supabase.com/dashboard/support/new

---

## ✅ **Checklist Final**

Pastikan `.env` Anda seperti ini:

```env
DB_CONNECTION=pgsql
DB_HOST=db.ugprcbrcdrbkxjitypne.supabase.co         ✅ Sudah benar
DB_PORT=5432                                         ✅ Sudah benar
DB_DATABASE=postgres                                 ✅ Sudah benar
DB_USERNAME=postgres.ugprcbrcdrbkxjitypne           ✅ Sudah benar
DB_PASSWORD=paste-password-anda-disini              ⚠️  GANTI INI!
DB_SSLMODE=require                                   ✅ Sudah benar
```

Hanya **DB_PASSWORD** yang perlu diganti! 🎯

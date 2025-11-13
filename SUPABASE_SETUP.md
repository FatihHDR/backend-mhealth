# 🚀 Integrasi Supabase PostgreSQL ke Laravel

## 📋 Langkah 1: Dapatkan Kredensial Database dari Supabase

### A. Login ke Supabase Dashboard
1. Buka: **https://app.supabase.com**
2. Login dengan akun Anda
3. Pilih project yang ingin digunakan (atau buat project baru)

### B. Ambil Database Credentials
1. Di dashboard project, klik **Settings** (icon gear ⚙️) di sidebar kiri bawah
2. Klik **Database** di menu Settings
3. Scroll ke bagian **Connection Info** atau **Connection String**

Anda akan melihat informasi seperti ini:
```
Host: db.xxxxxxxxxxxxxx.supabase.co
Database name: postgres
Port: 5432
User: postgres
Password: [your-password]
```

### C. Connection String (Alternative)
Atau bisa copy **Connection String** langsung:
```
postgresql://postgres:[YOUR-PASSWORD]@db.xxxxxxxxxxxxxx.supabase.co:5432/postgres
```

## 🔧 Langkah 2: Update File .env

Ganti nilai berikut di file `.env` Anda dengan kredensial dari Supabase:

```env
# Supabase PostgreSQL Configuration
DB_CONNECTION=pgsql
DB_HOST=db.xxxxxxxxxxxxxx.supabase.co        # Ganti dengan Host dari Supabase
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-supabase-password            # Ganti dengan password Anda
DB_SSLMODE=require                            # PENTING: Supabase requires SSL
```

### ⚠️ Catatan Penting:
- **DB_HOST**: Copy dari "Host" di Supabase (format: `db.xxxxx.supabase.co`)
- **DB_PASSWORD**: Ini password yang Anda set saat membuat project Supabase
- **DB_SSLMODE**: HARUS `require` karena Supabase memerlukan koneksi SSL

## 🧪 Langkah 3: Test Koneksi

Setelah update `.env`, test koneksi database:

```bash
# Clear config cache
php artisan config:clear

# Test koneksi
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected to Supabase successfully!';"

# Atau cek migration status
php artisan migrate:status
```

## 🗄️ Langkah 4: Jalankan Migration

Jika koneksi berhasil, jalankan migration:

```bash
# Jalankan semua migration
php artisan migrate

# Atau fresh migration (hapus semua tabel dulu)
php artisan migrate:fresh

# Fresh migration + seeder
php artisan migrate:fresh --seed
```

## 📊 Langkah 5: (Opsional) Lihat Database di Supabase

Setelah migration, Anda bisa lihat tabel yang terbuat:
1. Di Supabase Dashboard, klik **Table Editor** di sidebar
2. Anda akan melihat semua tabel yang baru dibuat

## 🔐 Tips Keamanan

### 1. **Jangan Commit Password ke Git**
Pastikan `.env` ada di `.gitignore`:
```gitignore
.env
.env.backup
```

### 2. **Gunakan Environment Variables di Production**
Saat deploy, set environment variables langsung di hosting, jangan hardcode di `.env`

### 3. **Batasi IP Access (Opsional)**
Di Supabase Settings → Database → Connection Pooling, Anda bisa batasi akses hanya dari IP tertentu.

## 🌐 Keuntungan Menggunakan Supabase

✅ **Free Tier Generous**: 500MB database, unlimited API requests  
✅ **Auto Backup**: Daily backups included  
✅ **Real-time Features**: Support untuk WebSockets/Real-time subscriptions  
✅ **Global CDN**: Fast access dari mana saja  
✅ **No Firewall Issues**: Port 5432 terbuka untuk koneksi remote  
✅ **SSL by Default**: Koneksi aman otomatis  

## 🔄 Switching Between Databases

### Gunakan Supabase (Production/Cloud):
```env
DB_CONNECTION=pgsql
DB_HOST=db.xxxxx.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-supabase-password
DB_SSLMODE=require
```

### Gunakan Local PostgreSQL (Development):
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=db_mhealth
DB_USERNAME=postgres
DB_PASSWORD=12345678
# DB_SSLMODE=prefer  # Optional untuk local
```

## 🆘 Troubleshooting

### Error: "could not connect to server"
- ✅ Pastikan Host benar (format: `db.xxxxx.supabase.co`)
- ✅ Cek password benar
- ✅ Pastikan `DB_SSLMODE=require` sudah di set

### Error: "password authentication failed"
- Reset password di Supabase Dashboard → Settings → Database → Reset Database Password

### Error: "SSL connection required"
- Pastikan `DB_SSLMODE=require` ada di `.env`
- Jalankan `php artisan config:clear`

### Koneksi Lambat
- Supabase server mungkin jauh dari lokasi Anda
- Pertimbangkan pilih region terdekat saat buat project baru

## 📞 Support

Jika ada masalah:
- **Supabase Docs**: https://supabase.com/docs
- **Community**: https://github.com/supabase/supabase/discussions
- **Discord**: https://discord.supabase.com

---

✅ **Setelah setup selesai, database Anda akan berjalan di cloud dan bisa diakses dari mana saja!**

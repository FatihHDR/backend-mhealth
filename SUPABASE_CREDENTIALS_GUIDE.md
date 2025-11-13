# 📝 Quick Guide: Cara Mendapatkan Kredensial Supabase

## Lokasi Kredensial Database

### Method 1: Via Connection Info (Recommended)

**Path**: Settings → Database → Connection Info

```
1. Login ke https://app.supabase.com
2. Pilih Project Anda
3. Klik "Settings" (⚙️) di sidebar bawah
4. Klik "Database"
5. Scroll ke "Connection Info"
```

Anda akan melihat:
```
Host
────────────────────────────────
db.xxxxxxxxxxxxxx.supabase.co

Database name
────────────────────────────────
postgres

Port
────────────────────────────────
5432

User
────────────────────────────────
postgres

Password
────────────────────────────────
[Click "Reset Database Password" if forgotten]
```

### Method 2: Via Connection String

**Path**: Settings → Database → Connection String

Pilih **URI** atau **Nodejs** mode, akan muncul:
```
postgresql://postgres:[YOUR-PASSWORD]@db.xxxxxxxxxxxxxx.supabase.co:5432/postgres
```

Format breakdown:
- **postgres** = username
- **[YOUR-PASSWORD]** = password Anda
- **db.xxxxxxxxxxxxxx.supabase.co** = host
- **5432** = port
- **postgres** = database name

## ⚠️ Lupa Password?

Jika lupa password:
1. Settings → Database
2. Klik tombol **"Reset Database Password"**
3. Masukkan password baru
4. Klik **"Reset Password"**
5. Update `.env` dengan password baru
6. Jalankan `php artisan config:clear`

## 🔑 Connection Parameters untuk Laravel

Copy nilai ini ke `.env` Anda:

| Supabase Field | Laravel .env Variable | Contoh Value |
|----------------|----------------------|--------------|
| Host | `DB_HOST` | `db.abcdefghijk.supabase.co` |
| Database name | `DB_DATABASE` | `postgres` |
| Port | `DB_PORT` | `5432` |
| User | `DB_USERNAME` | `postgres` |
| Password | `DB_PASSWORD` | `your-password-here` |
| - | `DB_CONNECTION` | `pgsql` |
| - | `DB_SSLMODE` | `require` |

## ✅ Final .env Configuration

```env
DB_CONNECTION=pgsql
DB_HOST=db.abcdefghijk.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-actual-password
DB_SSLMODE=require
```

## 🧪 Test Koneksi

```bash
php artisan config:clear
php artisan migrate:status
```

Jika berhasil, Anda akan melihat daftar migration! 🎉

# ⚡ Quick Reference: Supabase Database Connection

## 🔗 Direct Links

| Resource | URL |
|----------|-----|
| **Database Settings** | https://app.supabase.com/project/ugprcbrcdrbkxjitypne/settings/database |
| **Project Dashboard** | https://app.supabase.com/project/ugprcbrcdrbkxjitypne |
| **Table Editor** | https://app.supabase.com/project/ugprcbrcdrbkxjitypne/editor |
| **SQL Editor** | https://app.supabase.com/project/ugprcbrcdrbkxjitypne/sql |

## 📝 Your Database Credentials

```env
DB_CONNECTION=pgsql
DB_HOST=db.ugprcbrcdrbkxjitypne.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.ugprcbrcdrbkxjitypne
DB_PASSWORD=GET_FROM_SUPABASE_DASHBOARD
DB_SSLMODE=require
```

## 🎯 Cara Cepat Dapat Password

### Method 1: Dari Dashboard (Recommended)
```
1. Klik link: https://app.supabase.com/project/ugprcbrcdrbkxjitypne/settings/database
2. Scroll ke "Connection Info"
3. Lihat/Copy password
```

### Method 2: Reset Password (Jika Lupa)
```
1. Buka link yang sama
2. Klik "Reset Database Password"
3. Confirm
4. Copy password baru (only shown once!)
5. Update .env
```

### Method 3: Dari Connection String
```
1. Dashboard → Settings → Database
2. Pilih "Connection String" → "URI"
3. Password ada antara : dan @
   postgresql://user:[PASSWORD_HERE]@host...
```

## ✅ Test Koneksi

```bash
# Clear cache dulu
php artisan config:clear

# Test
php artisan migrate:status

# Jika sukses, jalankan migration
php artisan migrate
```

## 🚨 Troubleshooting

| Error | Solusi |
|-------|--------|
| `Access denied` | Password salah, reset password |
| `Connection timeout` | Check DB_HOST (harus ada `db.` prefix) |
| `SSL required` | Pastikan `DB_SSLMODE=require` |
| `could not connect` | Username harus `postgres.ugprcbrcdrbkxjitypne` |

## 💡 Tips

- ✅ Password case-sensitive
- ✅ Tidak perlu tanda kutip di .env
- ✅ Simpan password di password manager
- ⚠️  Jangan commit .env ke Git
- ⚠️  Password berubah setelah reset

## 📱 Contact Support

- Email: support@supabase.com
- Discord: https://discord.supabase.com
- Docs: https://supabase.com/docs

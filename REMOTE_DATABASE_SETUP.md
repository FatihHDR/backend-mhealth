# Panduan Mengaktifkan Remote MySQL di cPanel Rumahweb

## Langkah 1: Login ke cPanel
- Buka: https://panel.rumahweb.com atau email aktivasi dari Rumahweb
- Login dengan kredensial cPanel Anda

## Langkah 2: Buka Remote MySQL
1. Di cPanel, cari menu **"Remote MySQL"** atau **"Akses Database Jarak Jauh"**
2. Klik menu tersebut

## Langkah 3: Tambahkan IP Address
1. Di kolom "Add Access Host", masukkan IP public Anda
   
   **Cara cek IP public:**
   - Windows PowerShell: `curl ifconfig.me`
   - Browser: kunjungi https://whatismyipaddress.com/
   
2. Atau gunakan `%` untuk mengizinkan semua IP (TIDAK DISARANKAN untuk production!)

3. Klik tombol **"Add"**

## Langkah 4: Verifikasi
Setelah ditambahkan, coba jalankan:
```bash
php artisan migrate:status
```

## Troubleshooting

### ❌ Port 3306 Tertutup - Hasil Test:
```
TcpTestSucceeded : False
```

Ini berarti **shared hosting Rumahweb TIDAK mengizinkan remote MySQL access**.

### Solusi yang Tersedia:

#### 1. **Hubungi Support Rumahweb (Coba Dulu)**
   - Email: support@rumahweb.com
   - Live Chat: https://www.rumahweb.com
   - WhatsApp: 0804-1-808-888
   
   **Tanyakan:**
   - Apakah paket hosting Anda support remote MySQL?
   - Jika ya, minta dibukakan port 3306 untuk IP: (IP public Anda)
   - Jika tidak, tanyakan apakah bisa upgrade ke paket yang support

#### 2. **Gunakan Database Lokal untuk Development** ⭐ (RECOMMENDED)
   - Gunakan PostgreSQL/MySQL lokal di komputer Anda
   - Deploy ke server Rumahweb nanti (aplikasi + database di server yang sama)
   - Lebih cepat dan tidak perlu internet untuk development

#### 3. **Gunakan SSH Tunnel** (Jika SSH tersedia)
   - Butuh akses SSH ke server Rumahweb
   - Biasanya hanya tersedia di VPS, tidak di shared hosting

## Alternative: Gunakan SSH Tunnel (Lebih Aman)

Jika remote MySQL tidak bisa diaktifkan, gunakan SSH Tunnel:

```bash
# Di terminal terpisah, jalankan:
ssh -L 3306:localhost:3306 username@203.175.9.181

# Lalu di .env ubah DB_HOST menjadi:
DB_HOST=127.0.0.1
```

## Note Penting
⚠️ Untuk **PRODUCTION**, sebaiknya aplikasi Laravel di-deploy di server yang sama dengan database (tidak remote), untuk performa dan keamanan yang lebih baik.

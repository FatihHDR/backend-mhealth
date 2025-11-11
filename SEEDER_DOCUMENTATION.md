# Database Seeders - mHealth Backend

## 📦 Seeders yang Tersedia

Seeders telah dibuat untuk mengisi database dengan data initial dari file CSV.

### 1. **UserSeeder**
Membuat 3 user dengan role berbeda:
- **Super Admin** (`admin@mhealth.com` / `password123`)
- **Admin** (`admin.hospital@mhealth.com` / `password123`)
- **User** (`user@example.com` / `password123`)

### 2. **HospitalRelationSeeder**
Membuat 5 rumah sakit:
- Husada Utama
- RSUD Soewandi
- Rumah Sakit (Generic)
- RS Ngoerah Sun Bali
- RS Bethesda Yogyakarta

### 3. **MedicalPackageSeeder**
Import data dari `csv-files/medical.csv`:
- Membaca 23 paket medical dari file CSV
- Otomatis parse harga, duration, gender specificity
- Extract medical details dan included items
- Link dengan hospital yang sesuai

### 4. **LatestPackageSeeder**
Import data dari `csv-files/latest_package.csv`:
- Membaca 4 paket premium medical tourism
- Parse medical package, entertainment, hotel info
- Support duration (days/nights)
- Include hotel name dan entertainment activities

---

## 🚀 Cara Menjalankan Seeder

### Run All Seeders
```bash
php artisan db:seed
```

### Run Specific Seeder
```bash
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=HospitalRelationSeeder
php artisan db:seed --class=MedicalPackageSeeder
php artisan db:seed --class=LatestPackageSeeder
```

### Fresh Migration + Seed
```bash
php artisan migrate:fresh --seed
```

---

## 📊 Data yang Di-seed

Setelah menjalankan seeder, database akan berisi:
- ✅ **3 Users** (1 Super Admin, 1 Admin, 1 User)
- ✅ **5 Hospital Relations**
- ✅ **26 Medical Packages** (23 dari medical.csv + 3 dari latest_package.csv)

---

## 🔍 Fitur Seeder

### MedicalPackageSeeder
- ✅ Parse harga dari format Rupiah (Rp 35.500.000,00 → 35500000)
- ✅ Auto-detect gender dari judul/deskripsi
- ✅ Extract duration dari description
- ✅ Generate tagline otomatis
- ✅ Parse medical details ke JSON
- ✅ Extract included items

### LatestPackageSeeder
- ✅ Parse duration (3 Days / 2 Nights)
- ✅ Extract hotel information
- ✅ Parse medical package details
- ✅ Parse entertainment activities
- ✅ Determine gender specificity
- ✅ Support empty fields

### UserSeeder
- ✅ Create users dengan UUID
- ✅ Hash password menggunakan bcrypt
- ✅ JSON domicile (province, city, district, etc.)
- ✅ Sign-in device info
- ✅ Role-based users

---

## 📝 Format CSV yang Didukung

### medical.csv
```csv
Rumah Sakit,Paket,Keterangan,Harga
RS Name,Package Title,Description,Rp 1.000.000,00
```

### latest_package.csv
```csv
RS,Package,Medic,Tagline,Duration,Hotels,Entertain,Gender,Price
RS Name,Package,Medical Details,Tagline,3 Days / 2 Nights,Hotel Name,Activities,Male,Rp 10.000.000,00
```

---

## ⚙️ Customization

Untuk menambah data seeder, edit file seeder yang sesuai:

```php
// database/seeders/UserSeeder.php
$users[] = [
    'id' => Str::uuid(),
    'email' => 'newemail@example.com',
    'full_name' => 'New User',
    // ... fields lainnya
];
```

---

## 🔐 Default Credentials

Semua user memiliki password default: `password123`

**⚠️ PENTING**: Ganti password default sebelum production!

---

## ✅ Verifikasi

Cek jumlah data yang berhasil di-seed:

```bash
php artisan tinker
```

```php
DB::table('users')->count();           // Should be 3
DB::table('hospital_relation')->count(); // Should be 5
DB::table('package')->count();          // Should be 26
```

---

## 🎯 Next Steps

1. ✅ Run migration: `php artisan migrate`
2. ✅ Run seeder: `php artisan db:seed`
3. 🔨 Buat Models untuk setiap tabel
4. 🔨 Buat API endpoints
5. 🔨 Implement authentication

Selamat! Database Anda sudah terisi dengan data! 🎉

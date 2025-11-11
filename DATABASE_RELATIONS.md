# Database Relations - mHealth Backend

## 📋 Relasi Tabel

### ✅ Foreign Keys yang Diimplementasikan

#### 1. **users** → Multiple Tables (One-to-Many)

| Tabel Tujuan | Field | Relasi | On Delete |
|-------------|-------|--------|-----------|
| `payment` | `user_id` | users.id → payment.user_id | CASCADE |
| `chatbot` | `user_id` | users.id → chatbot.user_id | CASCADE |
| `article` | `user_id` | users.id → article.user_id | CASCADE |

---

### 📊 Relasi Logis (Application Level)

Relasi berikut dihandle di level aplikasi karena keterbatasan database foreign key:

#### 2. **package** / **medical_tech** → **payment** (Polymorphic)
- `payment.product_id` bisa merujuk ke:
  - `package.id` ATAU
  - `medical_tech.id`
- **Implementasi**: Gunakan kolom `payment_type` untuk membedakan jenis produk
  - `payment_type = 'package'` → product_id merujuk ke package
  - `payment_type = 'medical_tech'` → product_id merujuk ke medical_tech

#### 3. **hospital_relation** → **package** (Lookup)
- `package.hospital_name` mengacu pada `hospital_relation.name`
- `package.hospital_map` mengacu pada `hospital_relation.hospital_map`
- **Note**: Ini adalah relasi lookup, bukan foreign key karena berupa string

#### 4. **users.gender** → **package.spesific_gender** (Filter)
- Digunakan untuk filter/matching paket berdasarkan gender
- Bukan foreign key, tapi constraint enum yang sama

#### 5. **users.gender** → **medical_tech.spesific_gender** (Filter)
- Sama seperti package, untuk filter berdasarkan gender

#### 6. **package** → **recomendation_package** (Many-to-Many via JSON)
- `recomendation_package.package_id_list` berisi array UUID dari `package.id`
- Disimpan dalam format JSON karena many-to-many

---

## 🔍 Indexes yang Ditambahkan

Untuk meningkatkan performa query:

### Payment Table
- `product_id` - untuk join dengan package/medical_tech
- `status` - untuk filter status pembayaran
- `created_at` - untuk sorting berdasarkan waktu

### Package Table
- `is_medical` - filter paket medical
- `is_entertain` - filter paket entertainment
- `spesific_gender` - filter berdasarkan gender

### Medical Tech Table
- `spesific_gender` - filter berdasarkan gender

### Chatbot Table
- `public_token` - untuk lookup cepat berdasarkan public token
- `status` - filter berdasarkan status chat

### Article Table
- `category` - filter berdasarkan kategori
- `created_at` - sorting artikel terbaru

### Events Table
- `start_date` - filter/sorting event
- `end_date` - filter event yang sedang berlangsung

### Hospital Relation Table
- `name` - untuk lookup rumah sakit

---

## 📝 Catatan Implementasi

### 1. **Polymorphic Product Relationship**
```php
// Contoh implementasi di Model Payment
public function product()
{
    if ($this->payment_type === 'package') {
        return $this->belongsTo(Package::class, 'product_id');
    } elseif ($this->payment_type === 'medical_tech') {
        return $this->belongsTo(MedicalTech::class, 'product_id');
    }
    return null;
}
```

### 2. **Hospital Lookup**
```php
// Contoh di Model Package
public function hospital()
{
    return $this->hasOne(HospitalRelation::class, 'name', 'hospital_name');
}
```

### 3. **Recommendation Package**
```php
// Contoh di Model RecommendationPackage
public function packages()
{
    $ids = json_decode($this->package_id_list, true);
    return Package::whereIn('id', $ids)->get();
}
```

### 4. **User Relasi**
```php
// Model User
public function payments()
{
    return $this->hasMany(Payment::class);
}

public function articles()
{
    return $this->hasMany(Article::class);
}

public function chatbots()
{
    return $this->hasMany(Chatbot::class);
}
```

---

## ⚠️ Relasi yang Tidak Standar di DBML

Beberapa relasi di DBML Anda tidak dapat diimplementasikan sebagai foreign key karena alasan teknis:

1. **users.role → multiple tables**: Role adalah enum untuk authorization, bukan foreign key
2. **users.full_name → payment.full_name**: Nama disalin untuk snapshot data saat transaksi
3. **users.phone_number → payment.phone_number**: Sama seperti di atas
4. **users.email → payment.email**: Sama seperti di atas
5. **users.domicile → payment.address**: Format berbeda, domicile adalah JSON lengkap
6. **price fields**: Harga bukan foreign key, tapi nilai numerik untuk kalkulasi

Relasi-relasi ini dihandle di **application level** untuk menjaga data integrity dan business logic.

---

## 🚀 Summary

✅ **Foreign Keys Implemented**: 3 relasi (users → payment, chatbot, article)  
✅ **Indexes Added**: 15+ indexes untuk optimasi query  
✅ **Application-Level Relations**: Polymorphic, Lookup, dan JSON-based relations  

Database structure siap digunakan dengan performa optimal! 🎉

# Fast Food Management System

Zamonaviy fast food tizimi uchun boshqaruv paneli.

## Xususiyatlar

- 3 ta rol: Super Admin, Menejer, Sotuvchi
- Mahsulot va kategoriya boshqaruvi
- Buyurtmalarni qabul qilish
- Hisobotlar va statistika
- Responsive dizayn (mobil, planshet, desktop)
- Chiroyli ko'k rangli interfeys

## Texnologiyalar

- PHP
- MySQL
- HTML/CSS
- JavaScript (Vanilla)
- XAMPP server

## O'rnatish

1. XAMPP serverni ishga tushiring
2. `database/schema.sql` faylini import qiling:
   - phpMyAdmin ochib, `xpos_db` nomli database yarating
   - SQL faylni import qiling
3. Brauzerda `http://localhost/xpos` oching

## Default Login

**Super Admin:**
- Login: `admin`
- Parol: `admin123`

## Tuzilma

```
xpos/
├── api/                  # API endpoints
├── assets/
│   ├── css/             # Stillar
│   └── js/              # JavaScript fayllar
├── auth/                # Autentifikatsiya
├── config/              # Konfiguratsiya
├── database/            # SQL fayllar
├── helpers/             # Yordamchi funksiyalar
├── includes/            # Header/Footer
├── manager/             # Menejer paneli
├── seller/              # Sotuvchi paneli
├── super_admin/         # Super admin paneli
└── uploads/             # Yuklangan fayllar
```

## Litsenziya

© 2026 Fast Food Management System

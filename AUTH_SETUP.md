# Laravel Authentication dengan Spatie Permission

Dokumentasi implementasi authentication sistem menggunakan Laravel Auth dan Spatie Permission.

## File yang Dibuat

### 1. Controllers
- **`app/Http/Controllers/AuthController.php`** - Handle login, register, logout
  - `showLoginForm()` - Tampilkan form login
  - `login()` - Proses login
  - `showRegisterForm()` - Tampilkan form register
  - `register()` - Proses registrasi
  - `logout()` - Logout user

### 2. Models
- **`app/Models/User.php`** - Update dengan Spatie `HasRoles` trait
  - Integrasi dengan `spatie/laravel-permission`
  - Support untuk role dan permission management

- **`app/Models/Login.php`** - Model untuk tracking login history
  - `user_id` - Foreign key ke users
  - `email` - Email user saat login
  - `last_login_at` - Timestamp login terakhir
  - `last_login_ip` - IP address saat login
  - `login_count` - Jumlah total login

### 3. Database Migrations
- **`database/migrations/2026_02_15_020630_create_logins_table.php`** - Tabel untuk login history

### 4. Routes
- **`routes/web.php`** - Update dengan auth routes:
  - `GET /login` - Show login form
  - `POST /login` - Process login
  - `GET /register` - Show register form
  - `POST /register` - Process registration
  - `POST /logout` - Logout (protected)

### 5. Views
- **`resources/views/login.blade.php`** - Login page dengan demo user list
- **`resources/views/register.blade.php`** - Register page

### 6. Seeders
- **`database/seeders/RoleSeeder.php`** - Create semua 30 roles
- **`database/seeders/UserSeeder.php`** - Create test users dengan roles
- **`database/seeders/DatabaseSeeder.php`** - Main seeder yang call RoleSeeder dan UserSeeder

## User Roles untuk Loan System

Sistem mendukung 30 roles berikut untuk loan processing:
- **RM** - Relationship Manager
- **BM** - Branch Manager
- **BSM** - Business Service Manager
- **AM** - Account Manager
- **BAM** - Branch Account Manager
- **CIV Maker/Checker** - Credit Investigation Valuation
- **CS Maker/Checker** - Credit Score
- **DV Maker/Checker** - Document Valuation
- **Legal Maker/Checker** - Legal Review
- **OCR Maker/Checker** - Optical Character Recognition
- **Underwriter Maker/Checker** - Underwriting
- **PLI** - Payment Loss Insurance
- **PO Trade Maker/Checker** - Purchase Order Trade
- **PO Value Chain Maker/Checker** - Purchase Order Value Chain
- **Treasury Maker/Checker** - Treasury Operations
- **Unit Syariah Primover Maker/Checker** - Islamic Banking Unit
- **Valuer Internal/External** - Property Valuation
- **Credam Maker/Checker** - Credit Administration

## Cara Menggunakan

### 1. Install Dependencies
```bash
composer require spatie/laravel-permission
```

### 2. Publish Config dan Migration dari Spatie
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="config"
```

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Seed Database dengan Roles dan Test Users
```bash
php artisan db:seed
```

Ini akan:
- Create semua 30 roles di tabel `roles`
- Create 30 test users (satu per role) di tabel `users`
- Assign setiap user ke role yang sesuai

**Test Credentials:**
- Email: `test1@example.com` sampai `test30@example.com`
- Password: `password` (untuk semua)

### 5. Implementasi di Controller
```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Assign role ke user
$user->assignRole('RM');

// Check role
if ($user->hasRole('RM')) {
    // ...
}

// Check multiple roles
if ($user->hasAnyRole(['RM', 'BM'])) {
    // ...
}

// Get user roles
$roles = $user->getRoleNames();
```

## API Routes

### Login
```
POST /login
Body:
{
  "email": "user@example.com",
  "password": "password",
  "remember": true (optional)
}
```

### Register
```
POST /register
Body:
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

### Logout
```
POST /logout
```

## Middleware Authentication

Untuk protect routes gunakan middleware `auth`:
```php
Route::middleware('auth')->group(function () {
    // Protected routes
});
```

Untuk prevent authenticated users:
```php
Route::middleware('guest')->group(function () {
    // Only for guest
});
```

## Next Steps

1. Uncomment email verification di User model jika diperlukan
2. Setup email configuration untuk password reset
3. Buat roles dan permissions sesuai kebutuhan
4. Customisasi views sesuai dengan design aplikasi
5. Tambahkan 2FA (Two Factor Authentication) jika diperlukan

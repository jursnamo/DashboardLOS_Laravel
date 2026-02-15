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
- **`resources/views/login.blade.php`** - Login page
- **`resources/views/register.blade.php`** - Register page

## Cara Menggunakan

### 1. Install Dependencies
```bash
composer require spatie/laravel-permission
```

### 2. Publish Config dan Migration dari Spatie
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Implementasi di Controller
```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Assign role ke user
$user->assignRole('admin');

// Check role
if ($user->hasRole('admin')) {
    // ...
}

// Assign permission
$user->givePermissionTo('edit posts');
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

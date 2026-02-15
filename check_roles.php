<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use App\Models\User;

try {
    $roles = Role::pluck('name')->toArray();
    echo "Roles count: " . count($roles) . "\n";
    foreach ($roles as $r) {
        echo "- {$r}\n";
    }
} catch (Throwable $e) {
    echo "Error fetching roles: " . $e->getMessage() . "\n";
}

echo "\nSample users:\n";
$emails = ['test1@example.com', 'test30@example.com'];
foreach ($emails as $email) {
    $user = User::where('email', $email)->with('roles')->first();
    if ($user) {
        $roleNames = $user->roles->pluck('name')->toArray();
        echo "{$user->email} => {$user->name} => Roles: " . implode(', ', $roleNames) . "\n";
    } else {
        echo "{$email} => NOT FOUND\n";
    }
}

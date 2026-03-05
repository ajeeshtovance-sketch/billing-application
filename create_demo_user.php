<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    $user = User::create([
        'name' => 'Demo User 3',
        'username' => 'demo3',
        'email' => 'demo3@test.com',
        'password' => Hash::make('password'),
        'organization_id' => 1,
        'role' => 'user',
        'status' => 'active'
    ]);
    
    echo "✅ User created successfully!\n";
    echo "Username: demo3\n";
    echo "Password: password\n";
    echo "ID: " . $user->id . "\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

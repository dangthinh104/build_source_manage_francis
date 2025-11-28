<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking 2FA Status for All Users:\n";
echo "===================================\n\n";

$users = DB::table('users')->select('id', 'name', 'email', 'two_factor_enabled', 'two_factor_secret')->get();

foreach ($users as $user) {
    $status = $user->two_factor_enabled ? 'ENABLED ✓' : 'DISABLED ✗';
    $hasSecret = $user->two_factor_secret ? 'Yes' : 'No';
    echo "ID: {$user->id} | {$user->name} | {$user->email}\n";
    echo "   2FA Status: {$status} | Has Secret: {$hasSecret}\n\n";
}

echo "Total users: " . $users->count() . "\n";
echo "\nColumn 'two_factor_enabled' (boolean) = true/false\n";
echo "Column 'two_factor_secret' (text) = encrypted secret or null\n";


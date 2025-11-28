<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@example.com');
        $name = env('SUPER_ADMIN_NAME', 'Super Admin');
        $password = env('SUPER_ADMIN_PASSWORD', 'changeme');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'super_admin',
            ]);
            echo "Created super_admin user: {$email}\n";
        } else {
            $user->role = 'super_admin';
            $user->save();
            echo "Patched existing user to super_admin: {$email}\n";
        }
    }
}

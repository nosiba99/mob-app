<?php
// database/seeders/RolesAndAdminSeeder.php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء الأدوار
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);
        Role::firstOrCreate(['name' => 'driver']);

        // إنشاء الأدمن
        $admin = User::firstOrCreate(
            ['email' => 'admin@store.com'],
            [
                'first_name' => 'Admin',
                'last_name'  => 'User',

                'password'          => Hash::make('Admin@1234'),
                'email_verified_at' => now(), // الأدمن مفعّل مباشرة
            ]
        );

        $admin->assignRole('admin');
    }
}
<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@ecohydrotech.com.my'],
            [
                'name' => 'Ecohydrotech Administrator',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
                'status' => UserStatus::Active->value,
                'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
                'two_factor_recovery_codes' => [
                    'EHT-REC-0001',
                    'EHT-REC-0002',
                    'EHT-REC-0003',
                    'EHT-REC-0004',
                ],
                'two_factor_confirmed_at' => now(),
            ],
        );
        $admin->syncRolesBySlugs([RoleSlug::Administrator]);

        // Staff member holding both Instructor and Customer roles (D-026)
        $dualStaff = User::query()->updateOrCreate(
            ['email' => 'staff.trainee@ecohydrotech.com.my'],
            [
                'name' => 'Dr. Amirul Hakim',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
                'status' => UserStatus::Active->value,
            ],
        );
        $dualStaff->syncRolesBySlugs([RoleSlug::Instructor, RoleSlug::Customer], $admin->id);
        $dualStaff->customerProfile()->updateOrCreate(
            ['user_id' => $dualStaff->id],
            [
                'customer_type' => CustomerType::Individual->value,
                'phone' => '+6012-388 4501',
                'job_title' => 'Principal Hydrologist',
                'billing_country' => 'MY',
            ],
        );

        $instructor = User::query()->updateOrCreate(
            ['email' => 'instructor@ecohydrotech.com.my'],
            [
                'name' => 'Ir. Siti Norzahra',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
                'status' => UserStatus::Active->value,
            ],
        );
        $instructor->syncRolesBySlugs([RoleSlug::Instructor], $admin->id);

        $customer = User::query()->updateOrCreate(
            ['email' => 'trainee@ecohydrotech.com.my'],
            [
                'name' => 'Ahmad Faiz',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
                'status' => UserStatus::Active->value,
            ],
        );
        $customer->syncRolesBySlugs([RoleSlug::Customer]);
        $customer->customerProfile()->updateOrCreate(
            ['user_id' => $customer->id],
            [
                'customer_type' => CustomerType::Company->value,
                'phone' => '+6019-245 7710',
                'job_title' => 'Drainage Design Engineer',
                'company_name' => 'LembagaPerunding Hidro Sdn. Bhd.',
                'company_registration_no' => '201801024899 (1286919-W)',
                'billing_contact_name' => 'Puan Farah Nadia (Accounts)',
                'billing_email' => 'accounts@perundinghidro.com.my',
                'billing_phone' => '+603-8925 4400',
                'billing_address_line_1' => 'No. 14, Jalan Medan Pusat 2D, Seksyen 9',
                'billing_city' => 'Bandar Baru Bangi',
                'billing_state' => 'Selangor',
                'billing_postcode' => '43650',
                'billing_country' => 'MY',
            ],
        );
    }
}

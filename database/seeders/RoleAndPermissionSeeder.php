<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'slug' => RoleSlug::Administrator->value,
                'name' => 'Administrator',
                'description' => 'Full platform governance, catalogue, sessions, payments, attendance verification, certificates and settings.',
                'is_system' => true,
            ],
            [
                'slug' => RoleSlug::Instructor->value,
                'name' => 'Instructor',
                'description' => 'Assigned session logistics, participant rosters, attendance submission, completion notes and recording submission.',
                'is_system' => true,
            ],
            [
                'slug' => RoleSlug::Customer->value,
                'name' => 'Trainee / Customer',
                'description' => 'Public purchases, bookings, orders, learning materials, authorised recordings and certificates.',
                'is_system' => true,
            ],
        ];

        $roleModels = [];
        foreach ($roles as $roleData) {
            $roleModels[$roleData['slug']] = Role::query()->updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData,
            );
        }

        $permissions = [
            ['slug' => 'users.view', 'name' => 'View user accounts', 'module_group' => 'users'],
            ['slug' => 'users.manage', 'name' => 'Create, edit, suspend and assign roles to users', 'module_group' => 'users'],
            ['slug' => 'roles.manage', 'name' => 'Manage role assignments', 'module_group' => 'users'],
            ['slug' => 'modules.manage', 'name' => 'Manage training categories, modules and materials', 'module_group' => 'training'],
            ['slug' => 'sessions.manage', 'name' => 'Create, schedule, publish and cancel training sessions', 'module_group' => 'sessions'],
            ['slug' => 'bookings.manage', 'name' => 'View and administer physical session bookings', 'module_group' => 'commerce'],
            ['slug' => 'payments.manage', 'name' => 'Monitor payment attempts and webhook reconciliation', 'module_group' => 'commerce'],
            ['slug' => 'refunds.manage', 'name' => 'Approve, reject and process customer refunds', 'module_group' => 'commerce'],
            ['slug' => 'attendance.record', 'name' => 'Record and submit session attendance', 'module_group' => 'attendance'],
            ['slug' => 'attendance.verify', 'name' => 'Verify, return or correct session attendance', 'module_group' => 'attendance'],
            ['slug' => 'recordings.submit', 'name' => 'Submit session recordings for review', 'module_group' => 'recordings'],
            ['slug' => 'recordings.publish', 'name' => 'Review, approve or reject session recordings', 'module_group' => 'recordings'],
            ['slug' => 'certificates.approve', 'name' => 'Approve certificate eligibility and trigger PDF issuance', 'module_group' => 'certificates'],
            ['slug' => 'certificates.revoke', 'name' => 'Revoke or supersede issued certificates', 'module_group' => 'certificates'],
            ['slug' => 'announcements.manage', 'name' => 'Author and dispatch platform announcements', 'module_group' => 'operations'],
            ['slug' => 'reports.view', 'name' => 'View and export operational and financial reports', 'module_group' => 'operations'],
            ['slug' => 'settings.manage', 'name' => 'Configure platform system settings', 'module_group' => 'operations'],
            ['slug' => 'audit_logs.view', 'name' => 'Inspect immutable security and compliance audit logs', 'module_group' => 'operations'],
            ['slug' => 'instructor.access', 'name' => 'Access Instructor Portal', 'module_group' => 'portals'],
            ['slug' => 'portal.access', 'name' => 'Access Trainee / Customer Portal', 'module_group' => 'portals'],
        ];

        $permissionIdsBySlug = [];
        foreach ($permissions as $permissionData) {
            $permission = Permission::query()->updateOrCreate(
                ['slug' => $permissionData['slug']],
                $permissionData,
            );
            $permissionIdsBySlug[$permission->slug] = $permission->id;
        }

        // Administrators hold all operational & management permissions (not portal.access unless also assigned customer role per D-026)
        $adminPermissions = collect($permissionIdsBySlug)
            ->except(['portal.access', 'instructor.access'])
            ->values()
            ->all();
        $roleModels[RoleSlug::Administrator->value]->permissions()->sync($adminPermissions);

        // Instructors hold session-scoped operational permissions
        $roleModels[RoleSlug::Instructor->value]->permissions()->sync([
            $permissionIdsBySlug['instructor.access'],
            $permissionIdsBySlug['attendance.record'],
            $permissionIdsBySlug['recordings.submit'],
        ]);

        // Customers hold trainee portal access
        $roleModels[RoleSlug::Customer->value]->permissions()->sync([
            $permissionIdsBySlug['portal.access'],
        ]);
    }
}

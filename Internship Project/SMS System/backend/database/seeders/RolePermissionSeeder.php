<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public const ROLES = [
        'super_admin', 'admin', 'teacher', 'staff', 'student', 'parent',
    ];

    /**
     * Note: names follow the <resource>.<action> convention used by the
     * route/permission middleware throughout the API, e.g. users.manage.
     */
    public const PERMISSIONS = [
        'users.manage',
        'users.view',
        'teachers.manage',
        'teachers.view',
        'staff.manage',
        'staff.view',
        'students.manage',
        'students.view',
        'parents.manage',
        'parents.view',
        'courses.manage',
        'courses.view',
        'academic_years.manage',
        'categories.manage',
        'categories.view',
        'subjects.manage',
        'subjects.view',
        'schedules.manage',
        'schedules.view',
        'enrollments.manage',
        'enrollments.view',
        'attendance.record',
        'attendance.record.own',
        'attendance.view',
        'assessments.manage',
        'assessments.manage.own',
        'scores.enter',
        'scores.enter.own',
        'scores.view',
        'scores.view.own',
        'grades.view',
        'fees.manage',
        'fees.view',
        'payments.process',
        'payments.make',
        'payments.view',
        'announcements.manage',
        'settings.manage',
        'audit_logs.view',
        'contact_inquiries.view',
        'reports.export',
        'reports.view',
        'dashboard.admin',
        'dashboard.teacher',
        'messages.send',
        'messages.respond',
    ];

    public const ROLE_PERMISSIONS = [
        'super_admin' => '*',
        'admin' => '*',
        'teacher' => [
            'attendance.record.own',
            'attendance.view',
            'assessments.manage.own',
            'scores.enter.own',
            'scores.view.own',
            'grades.view',
            'courses.view',
            'students.view',
            'dashboard.teacher',
            'messages.send',
            'messages.respond',
        ],
        'staff' => [
            'users.view',
            'students.view',
            'parents.view',
            'teachers.view',
            'staff.view',
            'courses.view',
            'fees.view',
            'fees.manage',
            'payments.view',
            'payments.process',
            'payments.make',
            'announcements.manage',
            'contact_inquiries.view',
            'reports.view',
            'reports.export',
            'messages.send',
            'messages.respond',
        ],
        'student' => [
            'payments.make',
            'scores.view.own',
            'messages.respond',
        ],
        'parent' => [
            'payments.make',
            'scores.view.own',
            'messages.respond',
        ],
    ];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = Permission::pluck('name');

        foreach (self::ROLES as $roleName) {
            $role = Role::findOrCreate($roleName);

            if (self::ROLE_PERMISSIONS[$roleName] === '*') {
                $role->syncPermissions($all);
            } else {
                $role->syncPermissions(self::ROLE_PERMISSIONS[$roleName]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}

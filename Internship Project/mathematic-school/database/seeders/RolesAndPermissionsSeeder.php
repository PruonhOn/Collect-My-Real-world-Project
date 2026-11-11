<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Students
            'manage students',
            'view students',

            // Parents
            'manage parents',
            'view parents',

            // Teachers
            'manage teachers',
            'view teachers',

            // Admins
            'manage admins',

            // Categories
            'manage categories',

            // Courses
            'manage courses',
            'create courses',
            'edit courses',
            'publish courses',
            'delete courses',
            'view courses',

            // Lessons
            'manage lessons',
            'create lessons',
            'edit lessons',
            'delete lessons',

            // Quizzes
            'manage quizzes',
            'create quizzes',
            'edit quizzes',
            'delete quizzes',
            'view quiz results',

            // Assignments
            'manage assignments',
            'create assignments',
            'grade assignments',

            // Enrollments
            'manage enrollments',
            'enroll students',

            // Payments
            'manage payments',
            'view payments',

            // Certificates
            'manage certificates',
            'issue certificates',

            // Announcements
            'manage announcements',
            'create announcements',

            // Reports
            'view reports',

            // Settings
            'manage settings',

            // Roles
            'manage roles',

            // Notifications
            'view notifications',

            // Progress
            'view progress',

            // Grades
            'view grades',
            'manage grades',

            // Reviews
            'manage reviews',

            // Attendance
            'manage attendance',

            // Materials
            'manage materials',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $teacher = Role::firstOrCreate(['name' => 'teacher']);
        $parent = Role::firstOrCreate(['name' => 'parent']);
        $student = Role::firstOrCreate(['name' => 'student']);

        // Admin gets all permissions
        $admin->syncPermissions(Permission::all());

        // Teacher permissions
        $teacher->syncPermissions([
            'view courses',
            'create courses',
            'edit courses',
            'publish courses',
            'manage lessons',
            'create lessons',
            'edit lessons',
            'manage quizzes',
            'create quizzes',
            'edit quizzes',
            'view quiz results',
            'manage assignments',
            'create assignments',
            'grade assignments',
            'view students',
            'manage announcements',
            'create announcements',
            'view notifications',
            'view progress',
            'manage grades',
            'view grades',
            'manage materials',
        ]);

        // Parent permissions
        $parent->syncPermissions([
            'view notifications',
            'view payments',
        ]);

        // Student permissions
        $student->syncPermissions([
            'view courses',
            'view notifications',
            'view progress',
            'view grades',
        ]);
    }
}

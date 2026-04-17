<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Absences
        Schema::table('absences', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Absence Types
        Schema::table('absence_types', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Areas
        Schema::table('areas', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Roles
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Company Settings
        Schema::table('company_settings', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Hr Documents
        Schema::table('hr_documents', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Announcements
        Schema::table('announcements', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Vacation Years
        Schema::table('vacation_years', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Hr Document Audits
        Schema::table('hr_document_audits', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Holidays (si existe la tabla)
        if (Schema::hasTable('holidays')) {
            Schema::table('holidays', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
                $table->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'absences',
            'absence_types',
            'areas',
            'roles',
            'company_settings',
            'subscriptions',
            'hr_documents',
            'announcements',
            'vacation_years',
            'hr_document_audits',
            'holidays',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['tenant_id']);
                    $t->dropColumn(['tenant_id']);
                });
            }
        }
    }
};

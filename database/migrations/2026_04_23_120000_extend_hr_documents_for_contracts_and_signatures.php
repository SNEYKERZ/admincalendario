<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_documents', function (Blueprint $table) {
            $table->string('document_mode', 20)->default('user')->after('document_type');
            $table->foreignId('template_id')->nullable()->after('status')->constrained('hr_documents')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->after('template_id')->constrained('areas')->nullOnDelete();

            $table->string('signature_status', 20)->default('not_required')->after('area_id');
            $table->string('signature_method', 20)->nullable()->after('signature_status');
            $table->longText('signature_data')->nullable()->after('signature_method');
            $table->string('signature_name')->nullable()->after('signature_data');
            $table->timestamp('signature_requested_at')->nullable()->after('signature_name');
            $table->timestamp('signed_at')->nullable()->after('signature_requested_at');
            $table->foreignId('signed_by')->nullable()->after('signed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('signed_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->boolean('is_personal_upload')->default(false)->after('rejection_reason');

            $table->index(['document_mode', 'status']);
            $table->index(['signature_status', 'signed_at']);
            $table->index(['user_id', 'document_mode']);
        });
    }

    public function down(): void
    {
        Schema::table('hr_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');
            $table->dropConstrainedForeignId('area_id');
            $table->dropConstrainedForeignId('signed_by');

            $table->dropIndex(['document_mode', 'status']);
            $table->dropIndex(['signature_status', 'signed_at']);
            $table->dropIndex(['user_id', 'document_mode']);

            $table->dropColumn([
                'document_mode',
                'signature_status',
                'signature_method',
                'signature_data',
                'signature_name',
                'signature_requested_at',
                'signed_at',
                'rejected_at',
                'rejection_reason',
                'is_personal_upload',
            ]);
        });
    }
};

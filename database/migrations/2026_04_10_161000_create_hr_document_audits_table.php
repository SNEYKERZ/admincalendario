<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_document_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_document_id')->constrained('hr_documents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();

            $table->index(['hr_document_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_document_audits');
    }
};

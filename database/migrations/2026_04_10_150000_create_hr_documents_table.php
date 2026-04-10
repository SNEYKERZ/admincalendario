<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('document_type', 40)->default('documento');
            $table->string('status', 20)->default('activo');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index(['document_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_documents');
    }
};

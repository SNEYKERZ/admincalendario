<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacation_years', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('year');

            $table->integer('allocated_days')->default(15);
            $table->decimal('used_days', 5, 2)->default(0);

            $table->date('expires_at');

            $table->timestamps();

            $table->unique(['user_id', 'year']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_years');
    }
};
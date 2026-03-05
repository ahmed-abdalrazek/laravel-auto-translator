<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_key_id')
                  ->constrained('translation_keys')
                  ->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->text('value')->nullable();
            $table->boolean('is_auto_translated')->default(false);
            $table->string('provider')->nullable();
            $table->timestamps();

            $table->unique(['translation_key_id', 'locale']);
            $table->index(['locale', 'translation_key_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_values');
    }
};

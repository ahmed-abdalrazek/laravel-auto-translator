<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_memory', function (Blueprint $table) {
            $table->id();
            $table->text('source_text');
            // MD5 hash of source_text for fast indexed lookups (populated in the model/service layer)
            $table->string('source_hash', 32)->index();
            $table->string('source_lang', 10)->index();
            $table->string('target_lang', 10)->index();
            $table->text('translated_text');
            $table->string('provider', 50)->nullable()->index();
            $table->unsignedInteger('use_count')->default(1);
            $table->timestamps();

            $table->index(['source_hash', 'source_lang', 'target_lang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_memory');
    }
};

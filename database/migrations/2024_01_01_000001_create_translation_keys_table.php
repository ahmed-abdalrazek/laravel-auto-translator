<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->string('group')->default('*')->index();
            $table->string('file')->nullable()->index();
            $table->boolean('is_dead')->default(false)->index();
            $table->timestamps();

            $table->unique(['key', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_keys');
    }
};

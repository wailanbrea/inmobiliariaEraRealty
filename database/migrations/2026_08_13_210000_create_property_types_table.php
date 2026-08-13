<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_types', function (Blueprint $table) {
            $table->id();
            // JSON {"es": "Villa", "en": "Villa"} — ver docs/15_I18N.md 3.2
            $table->json('name');
            $table->string('slug', 120)->unique();
            $table->string('icon', 50)->nullable();   // Material Symbol
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_types');
    }
};

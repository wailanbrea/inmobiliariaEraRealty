<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amenidades como tabla propia, no como columna JSON en properties.
 *
 * Desviacion deliberada del prompt maestro (§8 sugeria amenities_json):
 * el diseno del listado filtra por amenidad, y un campo JSON no se puede
 * indexar para ese filtro ni mantiene un catalogo consistente
 * ("Piscina" / "piscina" / "Pisina").
 * Ver docs/12_KNOWN_ISSUES.md #6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug', 120)->unique();
            $table->string('icon', 50)->nullable();
            $table->string('category', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};

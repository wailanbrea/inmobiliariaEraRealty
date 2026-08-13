<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Textos de la propiedad, uno por idioma.
 *
 * Tabla y no columna JSON por dos razones concretas:
 *  1. El slug debe ser unico DENTRO de cada idioma (UNIQUE locale+slug),
 *     algo imposible con JSON.
 *  2. La busqueda por texto necesita FULLTEXT por idioma, y no se puede
 *     indexar el interior de un JSON.
 * Ver docs/15_I18N.md seccion 3.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->char('locale', 2);

            $table->string('title', 200);
            $table->string('slug', 220);
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();

            $table->string('meta_title', 200)->nullable();
            $table->string('meta_description', 300)->nullable();

            $table->timestamps();

            $table->unique(['property_id', 'locale']);
            $table->unique(['locale', 'slug']);
            $table->index('locale');
        });

        // FULLTEXT aparte: la sintaxis de Blueprint no lo cubre igual en
        // todos los motores, y en SQLite (pruebas) no existe.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE property_translations
                 ADD FULLTEXT ft_property_search (title, short_description, description)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('property_translations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla central del sistema.
 *
 * Aqui viven solo los datos comunes a ambos idiomas: precio, ubicacion,
 * habitaciones, imagenes... Los textos (titulo, slug, descripciones, SEO)
 * estan en property_translations. Una propiedad es UNA, con dos textos.
 *
 * Indices deducidos de los filtros reales del diseno
 * (propiedades_era_realty_rd/code.html).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code', 30)->unique();

            $table->enum('operation_type', ['sale', 'rent', 'temporary_rent', 'investment']);
            $table->foreignId('property_type_id')->constrained()->restrictOnDelete();

            $table->enum('status', [
                'draft', 'available', 'reserved', 'sold', 'rented', 'not_available', 'paused',
            ])->default('draft');

            // --- Precio ---
            $table->decimal('price', 15, 2)->nullable();   // null = a consultar
            $table->char('currency', 3)->default('USD');
            $table->enum('price_period', ['month', 'night', 'year'])->nullable();
            $table->decimal('maintenance_fee', 12, 2)->nullable();

            // --- Ubicacion ---
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address')->nullable();
            // Si es false, el mapa publico muestra un area aproximada.
            $table->boolean('show_exact_location')->default(false);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // --- Caracteristicas ---
            $table->unsignedTinyInteger('bedrooms')->nullable();
            // decimal: el diseno muestra 3.5 banos
            $table->decimal('bathrooms', 3, 1)->nullable();
            $table->unsignedTinyInteger('parking_spaces')->nullable();
            $table->decimal('construction_area', 10, 2)->nullable();   // m2
            $table->decimal('land_area', 10, 2)->nullable();           // m2
            $table->string('floor_level', 20)->nullable();
            $table->unsignedSmallInteger('year_built')->nullable();
            $table->boolean('is_furnished')->default(false);

            // --- Clasificacion ---
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_investment')->default(false);
            $table->boolean('is_project')->default(false);

            $table->json('features_json')->nullable();
            $table->string('video_url')->nullable();
            $table->string('virtual_tour_url')->nullable();

            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();

            // --- Datos privados del propietario: nunca salen al publico ---
            $table->string('owner_name', 150)->nullable();
            $table->string('owner_phone', 30)->nullable();
            $table->string('owner_email', 190)->nullable();
            $table->text('internal_notes')->nullable();

            $table->string('og_image')->nullable();
            $table->unsignedInteger('views_count')->default(0);

            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            // --- Indices ---
            $table->index(['status', 'published_at', 'deleted_at'], 'idx_public_list');
            $table->index(['operation_type', 'status'], 'idx_operation');
            $table->index(['property_type_id', 'status'], 'idx_type');
            $table->index(['province_id', 'city_id', 'sector_id'], 'idx_location');
            $table->index(['currency', 'price'], 'idx_price');
            $table->index(['bedrooms', 'bathrooms', 'parking_spaces'], 'idx_specs');
            $table->index(['is_featured', 'status', 'published_at'], 'idx_featured');
            $table->index(['is_investment', 'status'], 'idx_investment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};

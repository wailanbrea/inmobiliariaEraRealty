<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();

            // Rutas relativas al disco 'public'. Nunca absolutas: asi el sitio
            // sobrevive a un cambio de dominio o a mover la carpeta.
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('webp_path')->nullable();

            $table->string('original_name');
            $table->string('alt_text')->nullable();
            $table->string('title')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_main')->default(false);

            // width/height se guardan para poder emitirlos en el <img> y que
            // el layout no salte al cargar (CLS).
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('size');
            $table->string('mime_type', 50);

            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['property_id', 'sort_order']);
            $table->index(['property_id', 'is_main']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_images');
    }
};

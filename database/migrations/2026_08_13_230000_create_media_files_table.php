<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Biblioteca de medios reutilizable: logo, favicon, Open Graph, noticias,
 * agentes, banners y paginas.
 *
 * Las imagenes de propiedad NO viven aqui: tienen su propia tabla porque
 * llevan orden, imagen principal y pertenencia exclusiva a una ficha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 30)->default('public');

            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('webp_path')->nullable();

            $table->string('original_name');
            $table->string('mime_type', 50);
            $table->unsignedInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->string('alt_text')->nullable();
            $table->string('title')->nullable();

            // news | agent | page | banner | logo | og | general
            $table->string('context', 50)->nullable();
            $table->string('folder', 100)->nullable();

            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('context');
            $table->index('mime_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};

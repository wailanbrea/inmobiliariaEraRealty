<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenido editable de las paginas y de los bloques del inicio.
 *
 * Sin esto, los textos del diseno (el titular del hero, los rotulos de las
 * secciones) quedarian quemados en el Blade y el cliente no podria tocarlos,
 * que es justo lo que prohibe el prompt maestro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('slug', 120)->nullable();
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'published'])->default('published');
            // Las de sistema no se pueden borrar desde el panel.
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->char('locale', 2);

            $table->string('title', 200);
            $table->longText('content')->nullable();
            $table->string('meta_title', 200)->nullable();
            $table->string('meta_description', 300)->nullable();

            $table->timestamps();
            $table->unique(['page_id', 'locale']);
        });

        Schema::create('content_sections', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 50);
            $table->string('section_key', 50);
            $table->string('image')->nullable();
            $table->string('button_url')->nullable();
            $table->json('extra_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['page_key', 'section_key']);
            $table->index(['page_key', 'is_active']);
        });

        Schema::create('content_section_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_section_id')->constrained()->cascadeOnDelete();
            $table->char('locale', 2);

            $table->string('title', 200)->nullable();
            $table->string('subtitle', 300)->nullable();
            $table->text('content')->nullable();
            $table->string('button_text', 100)->nullable();

            $table->timestamps();
            $table->unique(['content_section_id', 'locale'], 'cst_section_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_section_translations');
        Schema::dropIfExists('content_sections');
        Schema::dropIfExists('page_translations');
        Schema::dropIfExists('pages');
    }
};

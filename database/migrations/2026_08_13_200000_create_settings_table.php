<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();

            $table->enum('type', [
                'string', 'text', 'boolean', 'integer', 'decimal',
                'json', 'image', 'email', 'url', 'select',
            ])->default('string');

            $table->string('group', 50)->index();

            // is_public = false nunca sale a las vistas publicas.
            // Protege credenciales SMTP y el correo receptor de formularios.
            $table->boolean('is_public')->default(true);

            // is_translatable = true guarda value como {"es": "...", "en": "..."}
            // Ver docs/15_I18N.md seccion 3.2.
            $table->boolean('is_translatable')->default(false);

            // is_encrypted = true cifra con Crypt antes de guardar.
            $table->boolean('is_encrypted')->default(false);

            $table->timestamps();

            $table->index(['group', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

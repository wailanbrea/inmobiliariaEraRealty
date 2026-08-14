<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // nullOnDelete y no cascadeOnDelete: si se borra un usuario, su
            // rastro tiene que sobrevivir. Un registro de auditoria que se
            // puede vaciar borrando al autor no sirve para auditar nada.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // El nombre se copia ademas de la clave foranea, por lo mismo:
            // si el usuario desaparece hay que seguir sabiendo quien fue.
            $table->string('user_name', 100)->nullable();

            $table->string('action', 40);
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();

            // Etiqueta legible de la entidad en el momento del hecho: el
            // titulo de la propiedad puede cambiar despues, o la fila puede
            // dejar de existir, y el listado seguiria teniendo que decir
            // sobre que se actuo.
            $table->string('entity_label', 200)->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();   // 45 = IPv6
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('created_at')->useCurrent();

            // El listado se ordena y filtra casi siempre por fecha.
            $table->index(['created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

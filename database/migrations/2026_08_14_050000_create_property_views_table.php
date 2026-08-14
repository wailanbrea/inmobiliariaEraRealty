<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visitas por propiedad y dia.
 *
 * DECISION: se guarda un CONTADOR AGREGADO POR DIA, no una fila por visita.
 *
 * Una fila por visita obligaria a guardar IP o identificador de sesion para
 * que sirviera de algo, y eso convierte una tabla de estadisticas en un
 * fichero de datos personales sujeto a la Ley 172-13 — con su deber de
 * informar, su plazo de conservacion y su riesgo si alguien la filtra. Para
 * responder "que propiedades interesan mas y en que semanas", que es lo unico
 * que el cliente pidio, basta con contar.
 *
 * A cambio se renuncia a saber "visitantes unicos". Es un precio razonable:
 * la deduplicacion por sesion que ya hace PropertyController evita contar los
 * refrescos, que era el problema real.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_views', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->date('viewed_on');
            $table->unsignedInteger('views')->default(0);

            // Una sola fila por propiedad y dia: el contador se incrementa
            // con un upsert, no se insertan filas nuevas.
            $table->unique(['property_id', 'viewed_on']);
            $table->index('viewed_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_views');
    }
};

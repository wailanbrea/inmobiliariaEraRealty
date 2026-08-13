<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('avatar');
            // Obliga a cambiar la contrasena generada por el seeder en el
            // primer inicio de sesion. Ver docs/10_TODO_MASTER.md pregunta 6.
            $table->boolean('must_change_password')->default(false)->after('is_active');

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['phone', 'avatar', 'is_active', 'must_change_password']);
        });
    }
};

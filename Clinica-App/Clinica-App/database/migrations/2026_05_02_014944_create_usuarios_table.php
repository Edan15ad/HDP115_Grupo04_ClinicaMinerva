<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->string('apellido', 50);
            $table->string('correo', 100)->unique();
            $table->text('password');
            $table->string('rol', 20);
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE usuarios
            ADD CONSTRAINT chk_usuarios_rol
            CHECK (rol IN ('paciente', 'recepcionista', 'laboratorio', 'administrador'))
        ");

        DB::statement("
            ALTER TABLE usuarios
            ADD CONSTRAINT chk_usuarios_estado
            CHECK (estado IN ('activo', 'inactivo'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examenes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 150)->nullable();
            $table->decimal('precio', 10, 2);
            $table->integer('tiempo_entrega_horas')->default(24);
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE examenes
            ADD CONSTRAINT chk_examenes_precio
            CHECK (precio >= 0)
        ");

        DB::statement("
            ALTER TABLE examenes
            ADD CONSTRAINT chk_examenes_tiempo_entrega
            CHECK (tiempo_entrega_horas > 0)
        ");

        DB::statement("
            ALTER TABLE examenes
            ADD CONSTRAINT chk_examenes_estado
            CHECK (estado IN ('activo', 'inactivo'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('examenes');
    }
};
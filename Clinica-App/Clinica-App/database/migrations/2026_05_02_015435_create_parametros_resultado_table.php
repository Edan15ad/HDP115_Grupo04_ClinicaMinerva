<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametros_resultado', function (Blueprint $table) {
            $table->id();

            $table->foreignId('examen_id')
                ->constrained('examenes')
                ->cascadeOnDelete();

            $table->string('nombre_parametro', 60);
            $table->string('etiqueta', 100);
            $table->string('tipo_dato', 20);
            $table->string('unidad_medida', 30)->nullable();
            $table->string('valor_referencia', 100)->nullable();
            $table->boolean('obligatorio')->default(false);
            $table->integer('orden_visual')->default(1);
            $table->string('estado', 20)->default('activo');

            $table->timestamps();

            $table->unique(['examen_id', 'nombre_parametro'], 'ux_parametro_examen_nombre');
            $table->index(['examen_id', 'orden_visual'], 'ix_parametros_resultado_examen');
        });

        DB::statement("
            ALTER TABLE parametros_resultado
            ADD CONSTRAINT chk_parametros_tipo_dato
            CHECK (tipo_dato IN ('texto', 'numero', 'decimal', 'booleano', 'fecha', 'opcion'))
        ");

        DB::statement("
            ALTER TABLE parametros_resultado
            ADD CONSTRAINT chk_parametros_estado
            CHECK (estado IN ('activo', 'inactivo'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros_resultado');
    }
};
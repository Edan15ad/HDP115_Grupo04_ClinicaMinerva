<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('detalle_orden_id')
                ->unique()
                ->constrained('detalle_ordenes')
                ->cascadeOnDelete();

            $table->timestamp('fecha_resultado')->useCurrent();
            $table->jsonb('resultado_json');
            $table->string('observaciones_generales', 200)->nullable();
            $table->string('archivo_pdf', 255)->nullable();
            $table->string('estado', 20)->default('borrador');
            $table->boolean('correo_enviado')->default(false);
            $table->timestamp('fecha_envio_correo')->nullable();

            $table->timestamps();

            $table->index('estado', 'ix_resultados_estado');
        });

        DB::statement("
            ALTER TABLE resultados
            ADD CONSTRAINT chk_resultados_estado
            CHECK (estado IN ('borrador', 'finalizado', 'enviado'))
        ");

        DB::statement("
            CREATE INDEX ix_resultados_json
            ON resultados
            USING GIN (resultado_json)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados');
    }
};
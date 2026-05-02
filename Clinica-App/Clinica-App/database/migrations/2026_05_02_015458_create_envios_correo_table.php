<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envios_correo', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resultado_id')
                ->constrained('resultados')
                ->cascadeOnDelete();

            $table->string('correo_destino', 100);
            $table->string('estado_envio', 20)->default('pendiente');
            $table->timestamp('fecha_envio')->nullable();
            $table->string('archivo_adjunto', 255)->nullable();
            $table->string('error_detalle', 255)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('resultado_id', 'ix_envios_correo_resultado');
            $table->index('estado_envio', 'ix_envios_correo_estado');
        });

        DB::statement("
            ALTER TABLE envios_correo
            ADD CONSTRAINT chk_envios_correo_estado
            CHECK (estado_envio IN ('pendiente', 'enviado', 'fallido'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('envios_correo');
    }
};
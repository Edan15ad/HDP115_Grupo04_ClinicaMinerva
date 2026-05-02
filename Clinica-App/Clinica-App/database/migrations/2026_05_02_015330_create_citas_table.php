<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->cascadeOnDelete();

            $table->date('fecha_cita');
            $table->time('hora_cita');
            $table->string('estado', 20)->default('agendada');

            $table->timestamps();

            $table->unique(['paciente_id', 'fecha_cita', 'hora_cita'], 'ux_citas_paciente_fecha_hora');
            $table->index(['fecha_cita', 'hora_cita'], 'ix_citas_fecha_hora');
        });

        DB::statement("
            ALTER TABLE citas
            ADD CONSTRAINT chk_citas_estado
            CHECK (estado IN ('agendada', 'confirmada', 'muestra_tomada', 'en_laboratorio', 'finalizada', 'cancelada'))
        ");

        DB::statement("
            ALTER TABLE citas
            ADD CONSTRAINT chk_hora_cita_rango
            CHECK (hora_cita >= TIME '08:00:00' AND hora_cita <= TIME '19:00:00')
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
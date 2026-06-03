<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes', function (Blueprint $table) {
            $table->id();

            $table->string('correlativo', 20)->unique();

            $table->foreignId('cita_id')
                ->unique()
                ->constrained('citas')
                ->cascadeOnDelete();

            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->cascadeOnDelete();

            $table->timestamp('fecha_orden')->useCurrent();
            $table->string('estado', 20)->default('pendiente');
            $table->decimal('total', 10, 2)->default(0);

            $table->timestamps();

            $table->index('paciente_id', 'ix_ordenes_paciente');
            $table->index('estado', 'ix_ordenes_estado');
        });

        DB::statement("
            ALTER TABLE ordenes
            ADD CONSTRAINT chk_ordenes_estado
            CHECK (estado IN ('pendiente', 'recepcionado', 'en_laboratorio', 'finalizado', 'entregado', 'cancelado'))
        ");

        DB::statement("
            ALTER TABLE ordenes
            ADD CONSTRAINT chk_ordenes_total
            CHECK (total >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
};
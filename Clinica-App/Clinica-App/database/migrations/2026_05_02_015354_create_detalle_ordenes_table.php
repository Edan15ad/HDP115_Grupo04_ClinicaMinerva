<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_ordenes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('orden_id')
                ->constrained('ordenes')
                ->cascadeOnDelete();

            $table->foreignId('examen_id')
                ->constrained('examenes')
                ->restrictOnDelete();

            $table->decimal('precio_unitario', 10, 2);
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('fecha_muestra')->nullable();
            $table->string('observaciones', 100)->nullable();

            $table->timestamps();

            $table->index('orden_id', 'ix_detalle_ordenes_orden');
            $table->index('examen_id', 'ix_detalle_ordenes_examen');
            $table->index('estado', 'ix_detalle_ordenes_estado');
        });

        DB::statement("
            ALTER TABLE detalle_ordenes
            ADD CONSTRAINT chk_detalle_ordenes_precio
            CHECK (precio_unitario >= 0)
        ");

        DB::statement("
            ALTER TABLE detalle_ordenes
            ADD CONSTRAINT chk_detalle_ordenes_estado
            CHECK (estado IN ('pendiente', 'muestra_tomada', 'en_proceso', 'finalizado', 'cancelado'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ordenes');
    }
};
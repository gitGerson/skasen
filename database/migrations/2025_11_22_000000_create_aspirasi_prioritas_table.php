<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aspirasi_prioritas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspirasi_id')
                ->constrained('aspirasis')
                ->cascadeOnDelete()
                ->unique();
            $table->enum('prioritas', ['Tinggi', 'Sedang', 'Rendah']);
            $table->decimal('confidence', 4, 3);
            $table->text('alasan_singkat');
            $table->string('model')->nullable();
            $table->string('vector_store_id')->nullable();
            $table->timestamp('classified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aspirasi_prioritas');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aspirasis', function (Blueprint $table) {
            $table->id();
            // Relasi ke users
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Relasi ke tujuan
            $table->foreignId('tujuan_id')
                ->constrained('tujuans')
                ->onDelete('cascade');

            // Relasi ke kategori
            $table->foreignId('kategori_id')
                ->constrained('kategoris')
                ->onDelete('cascade');

            $table->text('keterangan');
            $table->string('image_path')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aspirasis');
    }
};

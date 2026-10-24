<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('films', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('judul', 150);
            $table->string('sutradara', 100);
            $table->unsignedSmallInteger('tahun_rilis');
            $table->string('genre', 60);
            $table->decimal('rating', 2, 1)->nullable();
            $table->date('tanggal_ditonton')->nullable();
            $table->text('ulasan')->nullable();
            $table->boolean('favorit')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('films');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_tables', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 32)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->json('columns')->nullable();
            $table->unsignedInteger('rows_count')->default(0);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('data_table_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_table_id')->constrained('data_tables')->cascadeOnDelete();
            $table->json('data');
            $table->unsignedInteger('size_bytes')->default(0);
            $table->timestamps();

            $table->index(['data_table_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_table_rows');
        Schema::dropIfExists('data_tables');
    }
};

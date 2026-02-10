<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roads', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('designation', 50);
            $table->decimal('longitude', 5, 2);
            $table->decimal('latitude', 5, 2);
            $table->decimal('area', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roads');
    }
};
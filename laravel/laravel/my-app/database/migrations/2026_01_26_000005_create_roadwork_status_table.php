<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roadwork_status', function (Blueprint $table) {
            $table->unsignedBigInteger('roadwork_id');
            $table->integer('status_id');
            $table->timestamp('updated_at');
            $table->primary(['roadwork_id', 'status_id']);
            $table->foreign('roadwork_id')->references('id')->on('roadworks');
            $table->foreign('status_id')->references('id')->on('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadwork_status');
    }
};
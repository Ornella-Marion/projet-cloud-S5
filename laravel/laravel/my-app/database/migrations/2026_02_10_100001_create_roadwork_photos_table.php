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
        Schema::create('roadwork_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roadwork_id')->constrained('roadworks')->onDelete('cascade');
            $table->string('photo_url');
            $table->string('photo_path');
            $table->string('photo_type')->default('general'); // before, during, after, issue
            $table->text('description')->nullable();
            $table->dateTime('taken_at')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roadwork_photos');
    }
};

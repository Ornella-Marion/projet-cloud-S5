<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('email')->index();
            $table->string('ip_address')->nullable()->index();
            $table->boolean('success')->default(false)->index();
            $table->string('user_agent')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            
            // Index composés pour les requêtes de comptage
            $table->index(['email', 'success', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['user_id', 'success', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};

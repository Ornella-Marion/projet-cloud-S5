<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute les rôles: visitor, user, manager
     */
    public function up(): void
    {
        // Ajouter une contrainte CHECK pour les rôles valides
        // Cette migration assure que seuls les rôles valides sont acceptés
        Schema::table('users', function (Blueprint $table) {
            // Si PostgreSQL, utiliser un CHECK constraint
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("
                    ALTER TABLE users 
                    ADD CONSTRAINT valid_roles 
                    CHECK (role IN ('visitor', 'user', 'manager'))
                ");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS valid_roles");
            }
        });
    }
};

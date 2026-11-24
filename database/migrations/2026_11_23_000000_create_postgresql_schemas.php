<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS global;');
        DB::statement('CREATE SCHEMA IF NOT EXISTS master;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep schemas by default or drop cascade if needed
    }
};

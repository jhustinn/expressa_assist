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
        Schema::create('global.global_activity_log', function (Blueprint $table) {
            $table->bigIncrements('global_activity_id');
            $table->string('global_activity_action');
            $table->text('global_activity_description')->nullable();
            $table->string('global_activity_sender')->nullable();
            $table->string('global_activity_status')->default('success'); // success, warning, danger
            $table->json('global_activity_metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global.global_activity_log');
    }
};

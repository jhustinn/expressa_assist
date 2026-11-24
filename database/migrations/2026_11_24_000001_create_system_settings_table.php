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
        Schema::create('global.system_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general'); // paths, api_keys, gateway, profile, etc.
            $table->string('type')->default('string'); // string, password, boolean, integer, json
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global.system_settings');
    }
};

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
        // 1. Roles table
        Schema::create('global.roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique(); // admin, developer, reviewer, guest
            $table->string('label'); // Administrator, Developer, etc.
            $table->text('description')->nullable();
            $table->json('feature_permissions')->nullable(); // ['can_code', 'can_terminal', 'can_manage_users', etc.]
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // 2. Menus table
        Schema::create('global.menus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('section_key'); // core_chat, intelligence_tools, etc.
            $table->string('section_title'); // Core & Chat, etc.
            $table->integer('section_order')->default(0);
            $table->string('name'); // Interactive Chat, etc.
            $table->string('url'); // /chat
            $table->string('icon'); // fa-solid fa-terminal
            $table->string('badge')->nullable(); // Live, 31, etc.
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('roles')->nullable(); // ['admin', 'developer']
            $table->string('feature_code')->nullable(); // chat.view, users.manage, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global.menus');
        Schema::dropIfExists('global.roles');
    }
};

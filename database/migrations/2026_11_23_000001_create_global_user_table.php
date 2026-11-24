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
        Schema::create('global.global_user', function (Blueprint $table) {
            $table->bigIncrements('global_user_id');
            $table->string('global_user_nama');
            $table->string('global_user_username')->nullable();
            $table->string('global_user_email')->nullable();
            $table->string('global_user_password')->nullable();
            $table->string('global_user_telegram_id')->nullable()->unique();
            $table->string('global_user_role')->default('developer'); // admin, developer, reviewer, guest
            $table->boolean('global_user_can_code')->default(true);
            $table->boolean('global_user_can_terminal')->default(false);
            $table->boolean('global_user_is_active')->default(true);
            $table->json('global_user_allowed_commands')->nullable();
            $table->text('global_user_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global.global_user');
    }
};

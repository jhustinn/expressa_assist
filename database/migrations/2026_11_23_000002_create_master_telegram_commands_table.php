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
        Schema::create('master.master_telegram_command', function (Blueprint $table) {
            $table->bigIncrements('master_command_id');
            $table->string('master_command_command'); // /jhustyn, /erp, /status, /stop, /start
            $table->string('master_command_nama');
            $table->string('master_command_deskripsi')->nullable();
            $table->string('master_command_target_profile')->default('jhustyn');
            $table->text('master_command_prompt_template')->nullable();
            $table->boolean('master_command_is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master.master_telegram_command');
    }
};

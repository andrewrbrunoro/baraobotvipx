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
        Schema::create('bot_chats', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('bot_id');
            $table->bigInteger('chat_id');

            $table->bigInteger('verified_by')
                ->nullable();
            $table->dateTime('verified_at')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_chats');
    }
};

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
        Schema::create('auth_pins', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('bot_id');

            $table->string('chat_id', 60);

            $table->string('pin');

            $table->dateTime('verified_at')
                ->nullable();

            $table->dateTime('expire_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_pins');
    }
};

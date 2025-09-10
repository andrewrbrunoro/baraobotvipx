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
        Schema::create('commands', function (Blueprint $table) {
            $table->id();

            $table->boolean('only_owner')
                ->default(false);

            $table->bigInteger('user_id')
                ->nullable();

            $table->string('name', 20)
                ->nullable();
            $table->string('type', 60)
                ->nullable();

            $table->string('description')
                ->nullable();
            $table->string('aliases', 60)
                ->nullable();

            $table->longText('parameters')
                ->nullable();

            $table->boolean('default')
                ->default(true);

            $table->boolean('all_private_chats')
                ->default(false);

            $table->boolean('all_group_chats')
                ->default(false);

            $table->boolean('all_chat_administrators')
                ->default(false);

            $table->boolean('chat')
                ->default(false);

            $table->boolean('chat_administrators')
                ->default(false);

            $table->boolean('chat_member')
                ->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commands');
    }
};

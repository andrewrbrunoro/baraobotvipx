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
        Schema::create('chat_permissions', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('chat_id');

            $table->boolean('can_send_messages')
                ->nullable()
                ->default(false);

            $table->boolean('can_send_audios')
                ->nullable()
                ->default(false);

            $table->boolean('can_send_documents')
                ->nullable()
                ->default(false);

            $table->boolean('can_send_photos')
                ->nullable()
                ->default(false);

            $table->boolean('can_send_videos')
                ->nullable()
                ->default(false);

            $table->boolean('can_send_video_notes')
                ->nullable()
                ->default(false);

            $table->boolean('can_send_voice_notes')
                ->nullable()
                ->default(false);

            $table->boolean('can_send_polls')
                ->nullable()
                ->default(false);

            $table->boolean('can_send_other_messages')
                ->nullable()
                ->default(false);

            $table->boolean('can_add_web_page_previews')
                ->nullable()
                ->default(false);

            $table->boolean('can_change_info')
                ->nullable()
                ->default(false);

            $table->boolean('can_invite_users')
                ->nullable()
                ->default(false);

            $table->boolean('can_pin_messages')
                ->nullable()
                ->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_permissions');
    }
};

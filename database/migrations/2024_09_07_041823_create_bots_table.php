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
        Schema::create('bots', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id');

            $table->string('owner_code', 120)
                ->nullable();

            $table->string('code', 60)
                ->nullable();

            $table->string('token', 120);

            $table->string('first_name', 120)
                ->nullable();
            $table->string('username', 120)
                ->nullable();

            $table->boolean('can_join_groups')
                ->default(false);
            $table->boolean('can_read_all_group_messages')
                ->default(false);
            $table->boolean('supports_inline_queries')
                ->default(false);
            $table->boolean('can_connect_to_business')
                ->default(false);
            $table->boolean('has_main_web_app')
                ->default(false);

            $table->boolean('is_verified')
                ->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};

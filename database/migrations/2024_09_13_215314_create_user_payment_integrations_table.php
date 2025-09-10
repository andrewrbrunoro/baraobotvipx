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
        Schema::create('user_payment_integrations', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id');

            $table->string('platform', 60);

            $table->string('integration_code')
                ->nullable();

            $table->string('token_type')
                ->nullable()
                ->default('Bearer');

            $table->string('scope')
                ->nullable();

            $table->string('access_token')
                ->nullable();

            $table->string('refresh_token')
                ->nullable();

            $table->string('public_key')
                ->nullable();

            $table->bigInteger('expire_in')
                ->nullable();

            $table->dateTime('expire_at')
                ->nullable();

            $table->boolean('live_mode')
                ->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payment_integrations');
    }
};

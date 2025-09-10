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
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id');

            $table->string('code', 120);

            $table->string('auth_token', 120)
                ->nullable();

            $table->string('name', 120)
                ->nullable();
            $table->string('lastname', 120)
                ->nullable();

            $table->string('phone', 20)
                ->nullable();

            $table->string('username', 70)
                ->nullable();

            $table->string('email', 160)
                ->nullable();

            $table->string('language_code', 10)
                ->nullable()
                ->default('en');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};

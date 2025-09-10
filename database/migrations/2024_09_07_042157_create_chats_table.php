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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id');

            $table->string('code', 60)->nullable();
            $table->string('name', 120)->nullable();

            $table->decimal('price', 10)
                ->default(0.0)
                ->nullable();
            $table->decimal('price_sale', 10)
                ->default(0.0)
                ->nullable();

            $table->string('status', 20)
                ->default('ON');


            $table->boolean('is_group')
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
        Schema::dropIfExists('chats');
    }
};

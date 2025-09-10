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
        Schema::create('invite_links', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('member_id');

            $table->string('invite_link');

            $table->string('name', 120)
                ->nullable();
            $table->string('expire_date', 20)
                ->nullable();
            $table->integer('member_limit')
                ->nullable();
            $table->integer('pending_join_request_count')
                ->nullable();
            $table->integer('subscription_period')
                ->nullable();
            $table->integer('subscription_price')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invite_links');
    }
};

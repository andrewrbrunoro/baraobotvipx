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
        Schema::create('member_logs', function (Blueprint $table) {
            $table->id();

            $table->string('member_id', 60);
            $table->string('message_id', 60);

            $table->string('name', 120)
                ->nullable();

            $table->string('action', 60)
                ->nullable();

            $table->text('options')
                ->nullable();

            $table->text('feedback')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_logs');
    }
};

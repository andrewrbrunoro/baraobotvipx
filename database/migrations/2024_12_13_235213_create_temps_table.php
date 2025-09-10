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
        Schema::create('temps', function (Blueprint $table) {
            $table->id();

            $table->string('uuid', 120);

            $table->string('member_id')
                ->nullable();
            $table->string('name')
                ->nullable();
            $table->string('expired_at')
                ->nullable();
            $table->string('phone')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temps');
    }
};

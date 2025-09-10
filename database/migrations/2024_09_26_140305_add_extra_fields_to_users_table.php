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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 120)
                ->after('email')
                ->nullable();

            $table->string('photo_url')
                ->after('remember_token')
                ->nullable();

            $table->string('telegram_hash')
                ->after('telegram_owner_code')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'photo_url',
                'telegram_hash',
            ]);
        });
    }
};

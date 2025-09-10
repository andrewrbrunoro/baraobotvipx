<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarketings', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('PENDING');
            $table->timestamp('scheduled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('remarketings', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['bot_id']);
            $table->dropColumn(['member_id', 'campaign_id', 'bot_id', 'status', 'scheduled_at','updated_at']);
        });
    }
};

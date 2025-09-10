<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarketings', function (Blueprint $table) {
            if (!Schema::hasColumn('remarketings', 'campaign_id')) {
                $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            }
            if (!Schema::hasColumn('remarketings', 'bot_id')) {
                $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            }
            if (!Schema::hasColumn('remarketings', 'status')) {
                $table->string('status')->default('PENDING');
            }
            if (!Schema::hasColumn('remarketings', 'executed_at')) {
                $table->timestamp('executed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('remarketings', function (Blueprint $table) {
            if (Schema::hasColumn('remarketings', 'campaign_id')) {
                $table->dropForeign(['campaign_id']);
                $table->dropColumn('campaign_id');
            }
            if (Schema::hasColumn('remarketings', 'bot_id')) {
                $table->dropForeign(['bot_id']);
                $table->dropColumn('bot_id');
            }
            if (Schema::hasColumn('remarketings', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('remarketings', 'executed_at')) {
                $table->dropColumn('executed_at');
            }
        });
    }
};

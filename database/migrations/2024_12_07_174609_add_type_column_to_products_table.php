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
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 30)
                ->after('model_id')
                ->default('PRODUCT');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('type', 30)
                ->after('item_id')
                ->default('PRODUCT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

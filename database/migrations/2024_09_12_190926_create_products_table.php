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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id');

            $table->string('model_type', 60)
                ->nullable();
            $table->string('model_id', 60)
                ->nullable();

            $table->string('name', 60)
                ->nullable();

            $table->string('code', 120)
                ->nullable();

            $table->longText('description')
                ->nullable();

            $table->decimal('price', 10)
                ->nullable()
                ->default(0.0);
            $table->decimal('price_sale', 10)
                ->nullable()
                ->default(0.0);

            $table->bigInteger('duration_time')
                ->nullable()
                ->comment('NULL = infinito');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

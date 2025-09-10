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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('uuid', 64);

            $table->bigInteger('user_id');
            $table->bigInteger('member_id');

            $table->bigInteger('bot_id')
                ->nullable();
            $table->bigInteger('product_id')
                ->nullable();

            $table->string('item_type')
                ->comment('Adicione o nome da classe do item (exemplo: App/Product ou App/Chat) aqui');

            $table->string('item_id')
                ->comment('Adicione o id do item aqui');

            $table->decimal('price', 10)
                ->default(0.0)
                ->nullable()
                ->comment('Preço do item na hora da inserção');

            $table->decimal('price_sale', 10)
                ->default(0.0)
                ->nullable()
                ->comment('Preço promocional do item na hora da inserção');

            $table->decimal('total', 10)
                ->default(0.0)
                ->comment('Adicione o valor final da compra');

            $table->string('pix_code', 512)
                ->nullable();
            $table->string('payment_link', 512)
                ->nullable();

            $table->string('status', 20)
                ->default('WAITING');

            $table->string('platform', 30)
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

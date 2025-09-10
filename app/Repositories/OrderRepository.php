<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\Chat;
use App\Models\Order;
use App\Models\Product;

class OrderRepository extends Repository
{

    public function __construct()
    {
        parent::__construct(Order::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function find(int $order_id): ?Order
    {
        return $this->db
            ->find($order_id);
    }

    public function new(
        int        $user_id,
        int|string $member_id,
        int        $bot_id,
        Product    $product,
    ): ?Order
    {
        $item = app($product->model_type)->find($product->model_id);
        if (!$item) return null;

        return $this
            ->db
            ->create([
                'user_id' => $user_id,
                'member_id' => $member_id,
                'bot_id' => $bot_id,
                'product_id' => $product->id,
                'item_type' => $product->model_type,
                'item_id' => $product->model_id,
                'price' => $product->price,
                'price_sale' => $product->price_sale,
                'total' => $product->price_sale ?? $product->price,
                'pix_code' => '',
                'status' => 'WAITING',
            ]);
    }

}

<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Support;

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\Bot;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\OrderRepository;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;

class AppNotificationSupport
{

    public function __construct(
        protected Bot $bot
    )
    {
    }

    public static function make(Bot $bot): self
    {
        return new self($bot);
    }

    public function newOrder(Order $order): void
    {
        $product = Product::find($order->product_id);

//        $total = number_format($order->real_total, 2);
//        $this->telegram()
//            ->api()
//            ->sendMessage([
//                'text' => <<<HTML
//                🎉 Oba! Temos uma ótima notícia!
//
//                Acabamos de receber um novo pedido! 🙌✨
//
//                📋 Detalhes do pedido <b>#$order->id</b>
//                Produto: <b>$product->name</b>
//                🎫 Total: <b>$total</b>
//                HTML,
//                'parse_mode' => 'HTML',
//                'chat_id' => $this->bot->owner_code,
//                'reply_markup' => Keyboard::make()
//                    ->inline()
//                    ->setSelective(true)
//                    ->row([
//                        Button::make()
//                            ->setText(__('Visualizar no painel'))
//                            ->setUrl(ListOrders::getUrl())
//                    ])
//            ]);
    }

    protected function telegram(): BotTelegram
    {
        return BotTelegram::make($this->bot->token);
    }

}

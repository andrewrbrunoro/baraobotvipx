<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Models\Product;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;
use App\Services\Messengers\Telegram\Support\CallbackData;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;

class ProductListCommand extends Command
{
    use HelperCommand;

    protected array $aliases = [
        'ofertas',
        'planos',
        'produtos'
    ];

    public function handle(): void
    {
        $products = Product::where('user_id', $this->getUserId())
            ->whereNull('parent_id')
            ->get();

        if ($products->isEmpty()) {
            $this->replyWithMessage([
                'text' => 'Nenhum produto encontrado',
            ]);

            return;
        }

        foreach ($products as $product) {
            $text = __(
                $this->getPriceDescription($product),
                $product->toArray(),
            );

            $reply = $this->getReplyMarkup($product);

            $this->replyWithMessage([
                'text' => $text,
                'parse_mode' => 'MARKDOWN',
                'reply_markup' => $reply,
            ]);
        }
    }

    public function getReplyVariations(Product $product): Keyboard
    {
        $member = $this->getMember();

        $keyboard = Keyboard::make()
            ->inline()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true);

        $membersTest = [
            689161503,
            5822905454,
            7870336976,
            6138872863,
        ];

        $product->grids->map(function ($grid) use ($keyboard, $membersTest, $member) {
//            if (!in_array($member->code, $membersTest) && $grid->type === 'GIFT')
//                return;

            $keyboard->row([
                Button::make([
                    'text' => sprintf('%s - R$%s', $grid->name, $grid->price_sale > 0 ? $grid->price_sale : $grid->price),
                    'callback_data' => CallbackData::make('select_variation')
                        ->mergeData([
                            'id' => $grid->id
                        ])
                        ->get()
                ])
            ]);
        });

//        $product->grids->map(
//            fn($grid) => $keyboard->row([
//                Button::make([
//                    'text' => sprintf('%s - R$%s', $grid->name, $grid->price_sale > 0 ? $grid->price_sale : $grid->price),
//                    'callback_data' => CallbackData::make('select_variation')
//                        ->mergeData([
//                            'id' => $grid->id
//                        ])
//                        ->get()
//                ])
//            ])
//        );

        return $keyboard;
    }

    public function getReplyMarkup(Product $product): ?Keyboard
    {
        if ($product->grids)
            return $this->getReplyVariations($product);

        $payload = [
            'id' => $product->id,
        ];

        return Keyboard::make()
            ->inline()
            ->setResizeKeyboard(false)
            ->setSelective(true)
            ->row([
                Button::make([
                    'text' => __('PIX'),
                    'callback_data' => CallbackData::make('pix')
                        ->mergeData($payload)
                        ->get()
                ]),
                // Button::make([
                //     'text' => __('Cartão de Crédito'),
                //     'callback_data' => CallbackData::make('credit_card')
                //         ->mergeData($payload)
                //         ->get()
                // ])
            ]);
    }

    public function getUserId(): int
    {
        return $this->getBot()->user_id;
    }

    public function getPriceDescription(Product $product): string
    {
        if (!$product->description) {

            $description = 'Selecione um plano para continuar';
            if ($product->price > 0 || $product->price_sale > 0) {
                $name = '🛍️ **:name**';
                if ($product->price_sale > 0) {
                    $value = <<<HTML
                    💲 **De**: R$ :price
                    🔥 **Por**: R$ :price_sale
                    HTML;
                } else {
                    $value = '💲 **Valor**: R$ :price';
                }

                $description = '';
                if ($product->description) {
                    $description = <<<HTML
                    **Descrição**:
                    :description
                    HTML;
                }

                $description = <<<HTML
            $name

            $value
            $description
            HTML;
            }

        } else {
            $description = $product->description;
        }

        return <<<HTML
        $description
        HTML;
    }

    public function getDescription(): string
    {
        return __('Todos os produtos disponíveis na loja, não perca!');
    }

    public function getName(): string
    {
        return 'products';
    }

    public function getOnlyOwner(): bool
    {
        return false;
    }
}

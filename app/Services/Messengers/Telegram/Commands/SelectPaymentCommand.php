<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;
use App\Services\Messengers\Telegram\Support\CallbackData;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;

class SelectPaymentCommand extends Command
{
    use HelperCommand;

    protected string $name = 'select_payment';

    protected string $description = 'Selecionar a forma de pagamento disponível.';

    public function handle(): void
    {
        $this->replyWithMessage([
            'text' => __('Selecione a forma de pagamento'),
            'reply_markup' => $this->getReplyMarkup(),
        ]);
    }

    public function getReplyMarkup(): ?Keyboard
    {
        $product = [
            'id' => $this->getProductId(),
        ];

        return Keyboard::make()
            ->inline()
            ->setResizeKeyboard(true)
            ->setSelective(true)
            ->row([
                Button::make([
                    'text' => __('PIX'),
                    'callback_data' => CallbackData::make('pix')
                        ->mergeData($product)
                        ->get()
                ]),
                // Button::make([
                //     'text' => __('Cartão de Crédito'),
                //     'callback_data' => CallbackData::make('credit_card')
                //         ->mergeData($product)
                //         ->get()
                // ])
            ]);
    }

    public function getProductId(): int
    {
        return 1;
    }

    public function getName(): string
    {
        return 'select_payment';
    }

    public function getOnlyOwner(): bool
    {
        return false;
    }
}

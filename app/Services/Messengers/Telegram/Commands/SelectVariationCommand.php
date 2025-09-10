<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Jobs\Notifications\NewOrderJob;
use App\Models\Product;
use App\Services\Messengers\Telegram\Support\CallbackData;
use Telegram\Bot\Actions;
use App\Objects\OrderObject;
use App\Enums\PaymentPlatformEnum;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;
use App\Models\UserPaymentIntegration;
use App\Services\Messengers\Telegram\Commands\Traits\AuthCommand;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;

class SelectVariationCommand extends Command
{

    use AuthCommand,
        HelperCommand;

    protected string $name = 'select_variation';

    protected string $description = 'Seleção da variação';

    public function handle(): void
    {
        $callbackData = json_decode($this->getUpdate()->get('callback_query')->get('data'), true);
        $payload = $callbackData['payload'];

        $payload = [
            'id' =>  $payload['id'],
        ];

        $replyMarkup = Keyboard::forceReply(['selective' => false])
            ->inline()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Button::make([
                    'text' => __('PIX'),
                    'callback_data' => CallbackData::make('pix')
                        ->mergeData($payload)
                        ->get()
                ]),
            ]);
            // ->row([
            //     Button::make([
            //         'text' => __('Cartão de Crédito'),
            //         'callback_data' => CallbackData::make('credit_card')
            //             ->mergeData($payload)
            //             ->get()
            //     ])
            // ]);

        $this->replyWithMessage([
            'text' => __('Selecione a forma de pagamento'),
            'reply_markup' => $replyMarkup,
        ]);
    }
}

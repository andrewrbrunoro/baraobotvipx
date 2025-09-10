<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Jobs\Notifications\NewOrderJob;
use App\Models\Product;
use Telegram\Bot\Actions;
use App\Objects\OrderObject;
use App\Enums\PaymentPlatformEnum;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use App\Models\UserPaymentIntegration;
use App\Services\Messengers\Telegram\Commands\Traits\AuthCommand;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;

class CreditCardCommand extends Command
{

    use AuthCommand,
        HelperCommand;

    protected string $name = 'credit_card';

    protected string $description = 'Pagamento via cartão de crédito';

    public function handle(): void
    {
        $this->replyWithChatAction(['action' => Actions::TYPING]);

        $callbackData = json_decode($this->getUpdate()->get('callback_query')->get('data'), true);
        $payload = $callbackData['payload'];

        $productId = $payload['id'];

        $bot = $this->getBot();
        $member = $this->getMember();

        $userPaymentIntegration = UserPaymentIntegration::where('user_id', $bot->user_id)
            ->where('platform', PaymentPlatformEnum::MERCADO_PAGO)
            ->first();

        $orderObject = OrderObject::make(
            $bot->user_id,
            $bot,
            $member,
            $userPaymentIntegration
        )
            ->setProduct(Product::find($productId))
            ->purchase('link');

        if (!$orderObject) {
            $this->replyWithMessage([
                'text' => __('Não foi possível criar o seu pedido, tente novamente mais tarde.'),
                'reply_markup' => null,
            ]);
        } else {
            $replyMarkup = Keyboard::forceReply(['selective' => false])
                ->inline()
                ->setResizeKeyboard(false)
                ->setOneTimeKeyboard(true)
                ->row([
                    Keyboard::button([
                        'text' => __('Finalizar Pagamento'),
                        'url' => $orderObject->payment_link,
                    ]),
                ]);

            $this->replyWithMessage([
                'text' => __('Para sua segurança você será encaminhado para o link de pagamento do MercadoPago'),
                'reply_markup' => $replyMarkup,
            ]);

            dispatch(
                new NewOrderJob(
                    $this->getBot(),
                    $orderObject,
                )
            );
        }
    }
}

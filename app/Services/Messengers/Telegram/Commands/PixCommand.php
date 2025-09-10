<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Enums\PaymentPlatformEnum;
use App\Jobs\Notifications\NewOrderJob;
use App\Models\Product;
use App\Models\UserPaymentIntegration;
use App\Objects\OrderObject;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;

class PixCommand extends Command
{
    use HelperCommand;

    protected string $name = 'pix';

    protected string $description = 'Pagamento via PIX';

    public function handle(): void
    {
        $this->replyWithChatAction(['action' => Actions::TYPING]);

        $callbackData = json_decode($this->getUpdate()->get('callback_query')->get('data'), true);
        $payload = $callbackData['payload'];

        $productId = $payload['id'];

        $bot = $this->getBot();
        $member = $this->getMember();

        $userPaymentIntegration = UserPaymentIntegration::where('user_id', $bot->user_id)
            ->first();

        $orderObject = OrderObject::make(
            $bot->user_id,
            $bot,
            $member,
            $userPaymentIntegration
        )
            ->setProduct(Product::find($productId))
            ->purchase();

        if (!$orderObject) {
            $this->replyWithMessage([
                'text' => __('Não foi possível criar o seu pedido, tente novamente mais tarde.'),
                'reply_markup' => null,
            ]);
        } else {

            $this->replyWithMessage([
                'text' => __('Para realizar o pagamento, selecione a opção "Pagar com Pix", copie o código e cole no app do seu banco')
            ]);

            $this->replyWithMessage([
                'text' => __('Copie o código abaixo')
            ]);

            $this->replyWithMessage([
                'text' => "`$orderObject->pix_code`",
                'parse_mode' => 'MARKDOWN'
            ]);

            $pixPhoto = route('qrcode.image', $orderObject->id);

            $replyMarkup = Keyboard::forceReply(['selective' => false])
                ->inline()
                ->setResizeKeyboard(false)
                ->setOneTimeKeyboard(true)
                ->row([
                    Keyboard::button([
                        'text' => __('Visualizar imagem do pix'),
                        'url' => $pixPhoto,
                    ]),
                ]);

            $this->replyWithMessage([
                'text' => __('Outras opções'),
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

    public function getOnlyOwner(): bool
    {
        return false;
    }
}

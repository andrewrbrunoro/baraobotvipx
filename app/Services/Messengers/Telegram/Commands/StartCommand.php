<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Objects\RedirectUriObject;
use App\Repositories\AuthPinRepository;
use App\Repositories\BotCommandRepository;
use App\Repositories\CommandMessageRepository;
use App\Repositories\TelegramVideoRepository;
use App\Services\Messengers\ApproveBot;
use App\Services\Messengers\Telegram\Commands\Traits\AuthCommand;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use App\Services\Messengers\Telegram\Support\CallbackData;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;

class StartCommand extends Command
{
    use AuthCommand,
        HelperCommand;

    protected string $name = 'start';

    protected string $description = '';

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        if (!$this->isOwner())
            $this->handlePublic();
        else
            $this->handleAdmin();
    }

    public function handlePublic(): void
    {
        $bot = $this->getBot();
        $userId = $bot->user_id;

        $description = BotCommandRepository::make()
            ->description($bot->id, 'start');

        $result = TelegramVideoRepository::make()
            ->findByBotAndName($bot->token, 'start');

        $this->replyWithVideo([
            'video' => $result->telegram_id,
            'caption' => <<<HTML
            👉 Selecione o plano desejado, efetue o pagamento e receba o link de acesso em instantes automaticamente!
            HTML
        ]);

//        $this->triggerCommand('products');

        $this->replyWithMessage([
            'text' => '🔞 𝑬𝑵𝑻𝑹𝑬 𝑷𝑨𝑹𝑨 𝑶 𝑴𝑬𝑳𝑯𝑶𝑹 𝑮𝑹𝑼𝑷𝑶 𝑷𝑶𝑹𝑵𝑶 𝑫𝑶 𝑻𝑬𝑳𝑬𝑮𝑹𝑨𝑴  🔞',
            'reply_markup' => Keyboard::make()
                ->inline()
                ->row([
                    Button::make([
                        'text' => '🔥 Ver planos do VIP 🔥',
                        'callback_data' => CallbackData::make('products')
                            ->get()
                    ])
                ])
        ]);
    }

    public function handleAdmin(): void
    {
        $bot = $this->getBot();

        if ($bot->is_verified) {
            $this->replyWithMessage([
                'text' => <<<HTML
                    Seu BOT já está ativado e pronto para ser vinculado aos grupos/canais.
                HTML,
                'parse_mode' => 'HTML',
                'reply_markup' => Keyboard::make()
                    ->inline()
                    ->setSelective(true)
                    ->row([
                        Button::make()
                            ->setText('Gerenciar Grupo')
                            ->setUrl(sprintf('https://t.me/%s?startgroup=true', $bot->username))
                    ])
            ]);
        } else {

            $authPin = (new AuthPinRepository())
                ->generatePin($bot->id, $this->getChatId());

            $approveBot = ApproveBot::make($authPin, $this->getUserId())
                ->encrypt;

            $uri = RedirectUriObject::make(route('telegram.approve.bot', $approveBot), 1)
                ->generate();

            $replyMarkup = Keyboard::make()
                ->inline()
                ->setSelective(true)
                ->row([
                    Button::make()
                        ->setText('Ativar BOT')
                        ->setUrl($uri)
                ]);

            $this->replyWithMessage([
                'text' => <<<HTML
                    Clique no botão abaixo para Ativar o BOT ao seu painel
                HTML,
                'parse_mode' => 'HTML',
                'reply_markup' => $replyMarkup
            ]);
        }
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getOnlyOwner(): bool
    {
        return false;
    }
}

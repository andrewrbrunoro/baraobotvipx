<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Objects\RedirectUriObject;
use App\Repositories\AuthPinRepository;
use App\Services\Messengers\Telegram\Commands\Traits\AuthCommand;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;

class LoginCommand extends Command
{
    use AuthCommand,
        HelperCommand;

    protected string $name = 'login';

    protected string $description = 'Autenticar seu usuário';

    protected array $aliases = [
        '/gotcha'
    ];

    protected AuthPinRepository $authPinRepository;

    public function __construct()
    {
        $this->authPinRepository = new AuthPinRepository();
    }

    public function handle(): void
    {
        if (!$this->appBot())
            return;

        $userId = $this->getUpdate()->getMessage()->from->id;

        $short = RedirectUriObject::make(route('telegram.sync', $userId), 1)
            ->generate();

        $replyMarkup = Keyboard::make()
            ->inline()
            ->setSelective(true)
            ->row([
                Button::make()
                    ->setText('Autenticar')
                    ->setUrl($short)
            ]);

        $this->replyWithMessage([
            'text' => 'Clique no botão abaixo para confirmar sua conta',
            'parse_mode' => 'HTML',
            'reply_markup' => $replyMarkup
        ]);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function appBot(): bool
    {
        return $this->getBot()->token === env('APP_BOT_TELEGRAM_TOKEN');
    }

    public function getOnlyOwner(): bool
    {
        return false;
    }
}

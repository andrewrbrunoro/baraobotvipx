<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Models\ChatMember;
use App\Models\Gift;
use App\Models\InviteLink;
use App\Repositories\TelegramVideoRepository;
use App\Services\Messengers\Telegram\Commands\Traits\AuthCommand;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;

class GiftCommand extends Command
{
    use AuthCommand,
        HelperCommand;

    protected string $name = 'gift';

    protected string $description = '';

    protected string $pattern = '{token}';

    public function handle(): void
    {
        $member = $this->getMember();

        ///gift 84a0ae7a-70e9-46a7-a43a-925c44978a3c

        info(print_r($this->getArguments(), true));
        $text = $this->getUpdate()->getMessage()->get('text');
        if (empty($text))
            return;

        $token = str_replace('/gift ', '', $text);

        $gift = Gift::where('pin', $token)
            ->whereNull('burn')
            ->first();

        if (!$gift) {
            $this->replyWithMessage([
                'text' => 'Este presente não existe, você só pode tentar 3x ao dia.',
            ]);
        } else {

            $chatId = 7;

            $gift->burn = now();
            $gift->save();

            $chatMember = ChatMember::where('member_id', $member->id)
                ->where('chat_id', $chatId)
                ->first();

            if ($chatMember) {
                $chatMember->expired_at = $chatMember->expired_at->addMonth();
                $chatMember->save();
            } else {
                ChatMember::firstOrCreate([
                    'chat_id' => $chatId,
                    'member_id' => $member->id,
                ], [
                    'expired_at' => now()->addMonth(),
                    'already_kicked' => 0
                ]);
            }

            $inviteLink = InviteLink::first();

            $result = TelegramVideoRepository::make()
                ->findByBotAndName($this->getBot()->token, 'start');

            $this->replyWithVideo([
                'caption' => <<<HTML
                🌶️ Bem-vindo(a) ao grupo mais quente do Telegram! 🔥

                Você acabou de desbloquear acesso exclusivo a conteúdos que vão deixar sua imaginação à flor da pele. 💋

                🎁 Seu presente especial está aqui para tornar sua experiência ainda mais inesquecível! Aproveite cada momento e explore todos os benefícios que você merece.
                🔞 Este é um espaço seguro e respeitoso para quem sabe o que quer. Divirta-se e desfrute do melhor. 😉
                HTML,
                'video' => $result->telegram_id,
                'reply_markup' => Keyboard::make()
                    ->inline()
                    ->row([
                        Button::make()
                            ->setText('🔥 Clique aqui para acessar 🔥')
                            ->setUrl($inviteLink->invite_link)
                    ])
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

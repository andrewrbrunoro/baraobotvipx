<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram;

use App\Jobs\ChatJobs\AddMemberJob;
use App\Jobs\ChatJobs\RemoveMemberJob;
use App\Models\Bot;
use App\Models\Chat;
use App\Models\InviteLink;
use App\Models\TelegramVideo;
use App\Services\Messengers\ChatManagerInterface;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\ChatMember;
use App\Repositories\BotChatRepository;

class ChatTelegramManager implements ChatManagerInterface
{
    protected ?Chat $chat = null;

    protected Bot $bot;

    public function __construct(...$arguments)
    {
    }

    public static function make(...$arguments): self
    {
        return new self(...$arguments);
    }

    public function getChatMember(int|string $user_id, bool $return_bool = true): bool|ChatMember
    {
        try {

            info(print_r([
                'chat_id' => $this->getChat()->code,
                'user_id' => $user_id,
            ], true));

            $result = BotTelegram::make($this->getBot()->token)
                ->api()
                ->getChatMember([
                    'chat_id' => $this->getChat()->code,
                    'user_id' => $user_id,
                ]);

            if ($return_bool)
                return true;

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendMessage(int|string $user_id, string $message): self
    {
        try {

            $sendMessage = [
                'user_id' => $user_id,
                'text' => $message,
                'parse_mode' => 'HTML',
                'chat_id' => $this->getChat()->code ?? $user_id,
            ];

            BotTelegram::make($this->getBot()->token)
                ->api()
                ->sendMessage($sendMessage);

        } catch (\Exception $e) {
            report($e);
        }

        return $this;
    }

    public function asyncAddMember(int|string $user_id, string $message = ''): void
    {
        dispatch(
            new AddMemberJob(
                $this,
                $user_id,
                $message
            )
        );
    }

    public function addMember(mixed $params): bool
    {
        try {
            if (empty($params['user_id']))
                throw new \Exception("'user_id' não fornecido na funcao ChatTelegramManager@addMember");

            $chatCode = $params['chat_id'] ?? $this->getChat()->code;
            $userCode = $params['user_id'];

            $api = BotTelegram::make($this->getBot()->token)
                ->api();

            $invite = $this->inviteLink($params);

            $defaultMessage = <<<HTML
            Olá, delícia! 🔥 Temos uma novidade que vai fazer seu coração acelerar... 😏

            ✅ Seu pagamento foi aprovado com sucesso!
            Agora você está a um clique de acessar todo o prazer que preparamos especialmente para você. Use o botão abaixo e deixe a diversão começar! 💋

            💡 Como funciona?

            Clique no botão para solicitar acesso ao nosso canal exclusivo.
            Assim que aprovarmos sua entrada, enviaremos uma mensagem de confirmação aqui.
            O grupo também aparecerá automaticamente na sua lista de chats.

            Prepare-se para uma experiência inesquecível... 😈
            HTML;

            $api
                ->sendMessage([
                    'chat_id' => $userCode,
                    'text' => !empty($params['text'])
                        ? $params['text']
                        : $defaultMessage,
                    'reply_markup' => Keyboard::make()
                        ->inline()
                        ->setResizeKeyboard(false)
                        ->setSelective(true)
                        ->row([
                            Button::make()
                                ->setText('Acessar grupo')
                                ->setUrl($invite)
                        ])
                ]);

            $adminBot = BotChatRepository::make()->getDefaultBot();

            // Só faz o unban se a mensagem foi enviada com sucesso
            $adminApi = BotTelegram::make($adminBot->token)
                    ->api();
            try {
                $adminApi->getChatMember([
                    'chat_id' => $chatCode,
                    'user_id' => $userCode,
                ]);
            } catch (\Exception $e) {
                info('@addMember 148:', [
                    'error' => $e->getMessage(),
                ]);
            }

            if ($adminBot) {
                $adminApi = BotTelegram::make($adminBot->token)
                    ->api();

                try {
                    $adminApi->unbanChatMember([
                        'chat_id' => $chatCode,
                        'user_id' => $userCode,
                    ]);
                } catch (\Exception $e) {
                }
            }

            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    public function asyncRemoveMember(int|string $user_id): void
    {
        dispatch(
            new RemoveMemberJob(
                $this,
                $user_id,
            )
        );
    }

    public function removeMember(mixed $params, bool $send_message = true): bool
    {
        $adminBot = BotChatRepository::make()->getDefaultBot();

        $apiAdmin = BotTelegram::make($adminBot->token)
            ->api();
        try {
            $apiAdmin->banChatMember([
                'chat_id' => $this->getChat()->code,
                'user_id' => $params['user_id'],
            ]);
        } catch (\Exception $e) {
            info('@removeMember 185:', [
                'error' => $e->getMessage(),
            ]);
        }


        $api = BotTelegram::make($this->getBot()->token)
            ->api();

        try {
            if ($send_message) {
                $result = TelegramVideo::where('name', 'video_acabou')
                    ->first();

                $api
                    ->sendVideo([
                        'chat_id' => $params['user_id'],
                        'parse_mode' => 'HTML',
                        'video' => $result->telegram_id,
                        'caption' => !empty($params['text'])
                            ? $params['text']
                            : __('Uma pena você ter ido, espero que você volte o mais rápido possível!')
                    ]);
            }
        } catch (\Exception $e) {
            info('@removeMember 210:', [
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    public function inviteLink(mixed $params, ?int $max_use = null): ?string
    {
        try {
            $inviteLink = InviteLink::where('name', 'like', '%'. $this->getBot()->token .'%')
                ->first();

            if (!$inviteLink) {
                $result = BotTelegram::make(env('APP_BOT_TELEGRAM_TOKEN'))
                    ->api()
                    ->createChatInviteLink([
                        'chat_id' => $this->getChat()->code,
                        'creates_join_request' => true,
                    ]);

                $link = $result->invite_link;

                InviteLink::create([
                    'invite_link' => $link,
                    'member_id' => null,
                    'name' => $this->getBot()->token,
                    'expire_date' => $result->expire_date,
                    'member_limit' => $result->member_limit,
                    'pending_join_request_count' => $result->pending_join_request_count,
                    'subscription_period' => $result->subscription_period ?? null,
                    'subscription_price' => $result->subscription_price ?? null,
                ]);
            } else {
                $link = $inviteLink->invite_link;
            }

            return $link;
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    public function changeChatPermissions(array $params = []): self
    {
        try {

            $permissions = $this->getBlockPermissions();

            $telegram = BotTelegram::make($this->bot->token);
            $telegram->api()
                ->setChatPermissions([
                    'chat_id' => $this->getChat()->code,
                    'permissions' => $permissions,
                ]);
        } catch (\Exception $e) {
            report($e);
        }

        return $this;
    }

    public function setChat(Chat $chat): self
    {
        $this->chat = $chat;

        return $this;
    }

    public function setBot(Bot $bot): self
    {
        $this->bot = $bot;

        return $this;
    }

    public function getBot(): Bot
    {
        return $this->bot;
    }

    public function getChat(): ?Chat
    {
        return $this->chat;
    }

    private function getPermissions(bool $status): array
    {
        return [
            'can_send_messages' => $status, // Bloqueia o envio de mensagens
            'can_send_media_messages' => $status, // Bloqueia o envio de fotos, vídeos, etc.
            'can_send_polls' => $status, // Bloqueia o envio de enquetes
            'can_send_other_messages' => $status, // Bloqueia outros tipos de mensagens
            'can_add_web_page_previews' => $status, // Bloqueia a adição de previews de páginas web
            'can_change_info' => $status, // Bloqueia a mudança de informações do grupo
            'can_invite_users' => $status, // Bloqueia convites para novos usuários
            'can_pin_messages' => $status, // Bloqueia a fixação de mensagens
        ];
    }

    private function getBlockPermissions(): array
    {
        return $this->getPermissions(false);
    }

    private function getEnableAllPermissions(): array
    {
        return $this->getPermissions(true);
    }
}

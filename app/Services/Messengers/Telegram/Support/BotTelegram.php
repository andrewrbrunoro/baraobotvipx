<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Support;

use App\Models\Bot;
use App\Models\InviteLink;
use App\Models\TelegramVideo;
use App\Repositories\BotRepository;
use App\Repositories\ChatMemberRepository;
use App\Repositories\ChatRepository;
use App\Repositories\MemberLogRepository;
use App\Repositories\MemberRepository;
use App\Services\Messengers\Telegram\Commands\GiftCommand;
use App\Services\Messengers\Telegram\Commands\SelectVariationCommand;
use App\Services\Messengers\Telegram\Commands\StatusCommand;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User;

class BotTelegram
{
    protected BotRepository $botRepository;

    protected MemberRepository $memberRepository;

    protected ?Keyboard $replyMarkup = null;

    protected ?array $replyParameters = null;

    protected array $member = [];

    protected array $from = [];

    public function __construct(
        protected Api $telegram,
    )
    {
        $this->botRepository = new BotRepository();
        $this->memberRepository = new MemberRepository();
    }

    public static function make(string $bot_token): self
    {
        return new self(
            telegram: new Api($bot_token),
        );
    }

    public function webhook(): WebhookTelegram
    {
        return WebhookTelegram::make($this->telegram);
    }

    public function manageCommand(): CommandTelegram
    {
        return CommandTelegram::make($this->telegram, $this->getWebhookUpdate());
    }

    public function webhookUpdates(): Update
    {
        return $this->telegram->getWebhookUpdate();
    }

    public function getMessageFromUpdate(): array
    {
        $updates = $this->webhookUpdates();

        info(print_r($updates->toArray(), true));

        $message = $updates->getMessage();


        if ($updates->isType('chat_join_request')) {
            $bot = $this->getBot();
            $join = $updates->chatJoinRequest->toArray();

            $chat = ChatRepository::make()
                ->findByCode($join['chat']['id']);

            if (!$chat)
                exit;

            $from = MemberRepository::make()
                ->findByCode($join['from']['id']);

            if (!$from)
                exit;

            $exist = ChatMemberRepository::make()
                ->isMember(
                    $chat->id,
                    $from->id
                );

            if ($exist) {
                $botApi = BotTelegram::make($bot->token)
                    ->api();

                $status = false;
                try {
                    $status = $botApi->approveChatJoinRequest([
                        'chat_id' => $join['chat']['id'],
                        'user_id' => $join['from']['id'],
                    ]);
                } catch (TelegramResponseException $e) {
                    if (str_contains($e->getMessage(), 'USER_ALREADY_PARTICIPANT')) {
                        $status = true;
                    }
                }

                $invite = InviteLink::where('name', 'CHANNEL_' . $chat->id)
                    ->first();
                if ($invite) {
                    $result = TelegramVideo::where('name', 'request_join_accepted')
                        ->first();

                    $botApi
                        ->sendVideo([
                            'chat_id' => $join['from']['id'],
                            'video' => $result->telegram_id,
                            'caption' => <<<HTML
                            😈🔥 Ei, só pra te lembrar: seu acesso ao 𝑴𝑬𝑳𝑯𝑶𝑹 𝑮𝑹𝑼𝑷𝑶 𝑷𝑶𝑹𝑵𝑶 𝑫𝑶 𝑻𝑬𝑳𝑬𝑮𝑹𝑨𝑴 já está liberado!

                            💦 Não perde tempo! Tem conteúdo exclusivo e atualizado todos os dias só esperando por você.
                            HTML,
                            'reply_markup' => Keyboard::make()
                                ->inline()
                                ->setResizeKeyboard(false)
                                ->setSelective(true)
                                ->row([
                                    Button::make()
                                        ->setText('Acessar grupo')
                                        ->setUrl($invite->invite_link)
                                ])
                        ]);
                }
            }

            exit;
        } else if ($updates->isType('channel_post')) {
            $bot = $this->getBot();

            return [
                ...$updates->channelPost->toArray(),
                'from' => [
                    'id' => $bot->owner_code,
                    'from' => [
                        'id' => $bot->owner_code,
                        'firstName' => $bot->first_name,
                        'username' => $bot->username
                    ]
                ]
            ];
        }

        if (!$updates->isType('callback_query'))
            return $message->toArray();

        /**
         * @var CallbackQuery $callbackQuery
         */
        $callbackQuery = $updates->get('callback_query');
        $callbackData = $callbackQuery->get('data');

        if (json_validate($callbackData)) {
            $decodeCallbackData = json_decode($callbackData, true);
            $callbackQuery['data'] = $decodeCallbackData['data'];
        }

        return [
            ...$message->toArray(),
            'from' => $callbackQuery->from,
            'chat' => $callbackQuery->get('chat'),
            'text' => $callbackQuery['data'],
        ];
    }

    public function setupCommands(): self
    {
        if (!$this->isAppBot()) {
            $this->manageCommand()
                ->dbCommands($this->getBot());
        } else {
            $this->manageCommand()
                ->appBotCommands();
        }

        $this->telegram
            ->addCommand(SelectVariationCommand::class)
            ->addCommand(StatusCommand::class)
            ->addCommand(GiftCommand::class);

        return $this;
    }

    public function setupSpecificCommandsToMenu(array $commands, array $languages = ['pt']): self
    {
        $this->manageCommand()
            ->setMenuCommands($commands, $languages);

        return $this;
    }

    public function setupMember(): self
    {
        $payload = $this->getMessageFromUpdate();
        if (!isset($payload['from']))
            return $this;

        $userCode = $payload['from']['id'];
        $firstName = $payload['from']['first_name'] ?? '';
        $lastName = $payload['from']['last_name'] ?? '';
        $username = $payload['from']['username'] ?? '';
        $languageCode = $payload['from']['language_code'] ?? 'en';

        $this->memberRepository
            ->create(
                $this->getBot()->user_id,
                $this->getBot()->id,
                $userCode,
                [
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'username' => $username,
                    'language_code' => $languageCode,
                ],
            );

        MemberLogRepository::make()
            ->save(
                $payload['from']['id'],
                $payload['message_id'],
                $firstName . ' ' . $lastName,
                $payload['text'] ?? null,
                json_encode($payload['reply_markup'] ?? []),
                json_encode($payload['callback_query'] ?? []),
            );

        return $this;
    }

    public function api(): Api
    {
        return $this->telegram;
    }

    public function getMe(): User
    {
        return $this->telegram->getMe();
    }

    public function sendMessage(
        int|string $chat_id,
        string     $message,
    ): Message
    {
        return $this->telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $message,
            'reply_markup' => $this->getReplyMarkup(),
            'reply_parameters' => $this->getReplyParameters(),
        ]);
    }

    public function setReplyMarkup(Keyboard $keyboard): self
    {
        $this->replyMarkup = $keyboard;

        return $this;
    }

    public function setReplyParameters(
        int|string|null $chat_id,
        int             $message_id,
        array           $parameters
    ): self
    {
        $this->replyParameters = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'allow_sending_without_reply' => $parameters['allow_sending_without_reply'] ?? false,
        ];

        return $this;
    }

    public function messageData(Update $updates): array
    {
        $message = $updates->getMessage();
        if ($updates->isType('callback_query')) {
            $callbackQuery = $updates->get('callback_query');
            $callbackData = $callbackQuery->get('data');

            if (json_validate($callbackData)) {
                $decodeCallbackData = json_decode($callbackData, true);

                $callbackQuery['data'] = $decodeCallbackData['data'];
            }

            return [
                ...$message->toArray(),
                'text' => $callbackQuery['data'],
            ];
        }
        return $message->toArray();
    }

    public function prepareMember(Update $updates): ?\App\Models\Member
    {
        $message = $updates->getMessage();
        $from = $message->from;
        if ($updates->isType('callback_query')) {
            $callbackQuery = $updates->get('callback_query');
            $from = $callbackQuery->from;
        }

        return \App\Models\Member::updateOrCreate([
            'user_id' => 1,
            'code' => $from->id,
        ], [
            'name' => trim(sprintf('%s %s', $from->first_name, $from->last_name ?? '')),
            'username' => $from->username,
            'language_code' => $from->language_code,
        ]);
    }

    public function run(): void
    {
        $updates = $this->getWebhookUpdate();
        $messageData = $this->messageData($updates);

        if (!str_contains($messageData['text'] ?? '', '/'))
            return;

        if (str_contains($messageData['text'], 'gift')) {
            $explode = explode(' ', $messageData['text']);
            $command = 'gift';
        } else {
            $command = str_replace('/', '', $messageData['text'] ?? 'help');
        }

        info('=> command: ' . $command);

        $this->api()
            ->triggerCommand(
                $command,
                $updates
            );
    }

    public function isAppBot(): bool
    {
        return $this->getBot()->token === env('APP_BOT_TELEGRAM_TOKEN');
    }

    public function getBot(): Bot
    {
        return $this->botRepository->findByToken(
            token: $this->telegram->getAccessToken(),
        );
    }

    public function getReplyParameters(): ?array
    {
        return $this->replyParameters;
    }

    public function getReplyMarkup(): ?Keyboard
    {
        return $this->replyMarkup;
    }

    public function getWebhookUpdate(): Update
    {
        $result = $this->api()
            ->getWebhookUpdate();

//        $result->getMessage()->put('message', '/how_to_setup_group');

        return $result;
    }
}

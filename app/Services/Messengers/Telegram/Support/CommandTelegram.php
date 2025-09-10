<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Support;

use App\Exceptions\Cache\BotCacheEnum;
use App\Models\Bot;
use App\Models\Command;
use App\Repositories\CommandRepository;
use App\Services\Messengers\Telegram\Commands\CreditCardCommand;
use App\Services\Messengers\Telegram\Commands\CustomCommand;
use App\Services\Messengers\Telegram\Commands\HelpCommand;
use App\Services\Messengers\Telegram\Commands\PixCommand;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class CommandTelegram
{

    protected array $commands = [];

    protected string $scope = '';

    protected string $languageCode = 'en';

    public function __construct(
        protected Api     $telegram,
        protected ?Update $update = null,
    )
    {
    }

    public static function make(Api $telegram, ?Update $update): self
    {
        return new self($telegram, $update);
    }

    public function appBotCommands(): self
    {
        $commands = CommandRepository::make()
            ->appCommands()
            ->map(function (Command $command) {
                if (empty($command->type))
                    return CustomCommand::init($command);
                else {
                    return $command->type;
                }
            })
            ->toArray();

        $this->setCommands([
            HelpCommand::class,
            ...$commands
        ]);

        $this->telegram
            ->addCommands([
                HelpCommand::class,
                ...$commands
            ]);

        return $this;
    }


    public function dbCommands(Bot $bot): self
    {
        $update = $this->getUpdate();
        if ($update->isType('channel_post')) {
            $chatType = 'group';
        } else {
            $chatType = $update->getChat()->type;
        }

        $commands = CommandRepository::make()
            ->botCommands($bot->id, CommandRepository::$chatTypes[$chatType])
            ->map(function (Command $command) {
                if (empty($command->type))
                    return CustomCommand::init($command);
                else {
                    return $command->type;
                }
            })
            ->toArray();

        $commands = [
            ...$commands,
            ...$this->systemCommands(),
        ];

        $this->telegram
            ->addCommands($commands);

        return $this;
    }

    public function setMenuCommands(array $commands, array $languages = ['pt']): self
    {
        $scope = 'default';

//        foreach ($languages as $language) {
//            $this->telegram->setMyCommands([
//                'commands' => $commands,
//                'scope' => ['type' => $scope],
//                'language_code' => $language,
//            ]);
//        }

        return $this;
    }

    /**
     * Adicione os comandos com scope default
     *
     * @return $this
     */
    public function menuCommands(): self
    {
        $scope = 'default';

        $this->telegram->setMyCommands([
            'commands' => $this->getCommands(),
            'scope' => ['type' => $scope],
            'language_code' => $this->getLanguageCode(),
        ]);

        return $this;
    }

    /**
     * Comandos que vão executar caso executando um callback específico, exemplo as formas de pagamento
     *
     * @return string[]
     */
    public function systemCommands(): array
    {
        return [
            PixCommand::class,
            CreditCardCommand::class,
        ];
    }

    public function registerBotCommands(Bot $bot): self
    {
        if ($bot->token === env('APP_BOT_TELEGRAM_TOKEN'))
            return $this->appBotCommands();

        if (!$bot->commands)
            return $this;

        $registerCommands = [];
        foreach ($bot->commands as $command) {
            if (!($command->command instanceof Command))
                continue;

            if (empty($command->command->type))
                $registerCommands[] = CustomCommand::init($command->command);
            else {
                $registerCommands[] = $command->command->type;
            }
        }

        $user = $bot->user;
        foreach ($user->commands ?? [] as $command) {
            if (empty($command->type))
                $registerCommands[] = CustomCommand::init($command);
            else {
                $registerCommands[] = $command->type;
            }
        }

        $everyOneCommands = Cache::remember(BotCacheEnum::FREE_COMMANDS, now()->addDay(), function () {
            return Command::whereNull('user_id')
                ->get();
        });

        foreach ($everyOneCommands as $command) {
            $registerCommands[] = CustomCommand::init($command);
        }

        $this->telegram
            ->addCommands($registerCommands);

        return $this;
    }

    public function setCommands(array $commands, string $language_code = 'en'): self
    {
        $this->commands = array_map(function ($command) {
            $instance = is_string($command) ? app($command) : $command;
            return [
                'command' => sprintf('/%s', str_replace('/', '', $instance->getName())),
                'description' => $instance->getDescription(),
            ];
        }, $commands);

        $this->languageCode = $language_code;

        return $this;
    }

    public function toMenu(): bool
    {
        return $this->toAllGroupsAndChats();
    }

    public function toAllGroupsAndChats(): bool
    {
        $scope = 'all_group_chats';

        return $this->telegram->setMyCommands([
            'commands' => $this->getCommands(),
            'scope' => ['type' => $scope],
            'language_code' => $this->languageCode,
        ]);
    }

    public function toPrivateChats(): bool
    {
        $scope = 'all_private_chats';

        return $this->telegram->setMyCommands([
            'commands' => $this->getCommands(),
            'scope' => ['type' => $scope],
            'language_code' => $this->getLanguageCode(),
        ]);
    }

    public function toOwnerChat(
        int|string $owner_chat_id
    ): bool
    {
        $scope = 'chat_member';

        return $this->telegram->setMyCommands([
            'commands' => $this->getCommands(),
            'scope' => [
                'type' => $scope,
                'chat_id' => $owner_chat_id,
                'user_id' => $owner_chat_id
            ],
            'language_code' => $this->getLanguageCode(),
        ]);
    }

    public function toAdminChat(): bool
    {
        $scope = 'all_chat_administrators';

        return $this->telegram->setMyCommands([
            'commands' => $this->getCommands(),
            'scope' => ['type' => $scope],
            'language_code' => $this->getLanguageCode(),
        ]);
    }

    public function setLanguageCode(string $language_code): self
    {
        $this->languageCode = $language_code;

        return $this;
    }

    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getTelegram(): Api
    {
        return $this->telegram;
    }

    public function getUpdate(): ?Update
    {
        return $this->update;
    }
}

<?php declare(strict_types=1);

namespace App\Objects;

use App\Models\Command;
use App\Services\Messengers\Telegram\Objects\ParametersObject;
use Dotenv\Util\Str;

class CreateCommand
{
    protected bool $only_owner = false;

    protected ?string $type = null;

    protected string $description = '';

    protected array $aliases = [];

    protected ?ParametersObject $parameters = null;

    protected bool $appCommand = false;

    protected bool $default = true;

    protected bool $allPrivateChats = false;

    protected bool $allGroupChats = false;

    protected bool $allChatAdministrators = false;

    protected bool $chat = false;

    protected bool $chatAdministrators = false;

    protected bool $chatMember = false;

    public function __construct(
        protected string $command_name,
        protected ?int   $user_id = null,
        protected string $pattern = ''
    )
    {
    }

    public static function make(
        string $command_name,
        ?int   $user_id = null,
        string $pattern = ''
    ): self
    {
        return new self($command_name, $user_id, $pattern);
    }

    public function save(): ?Command
    {
        return Command::updateOrCreate([
            'user_id' => $this->getUserId(),
            'name' => $this->getCommandName(),
            'type' => $this->getType(),
        ], [
            ...$this->toArray(),
            'parameters' => serialize($this->parameters),
        ]);
    }

    public function toArray(): array
    {
        return [
            'app_command' => $this->isAppCommand(),
            'only_owner' => $this->getOnlyOwner(),
            'user_id' => $this->getUserId(),
            'type' => $this->getType(),
            'name' => $this->getCommandName(),
            'description' => $this->getDescription(),
            'aliases' => $this->getAliases(),
            'parameters' => $this->getParameters(),
            'default' => $this->isDefault(),
            'all_private_chats' => $this->isAllPrivateChats(),
            'all_group_chats' => $this->isAllGroupChats(),
            'all_chat_administrators' => $this->isAllChatAdministrators(),
            'chat' => $this->isChat(),
            'chat_administrators' => $this->isChatAdministrators(),
            'chat_member' => $this->isChatMember(),
        ];
    }

    public function isOnlyOwner(): self
    {
        $this->only_owner = true;

        $this->setChatAdministrators(true);

        return $this;
    }

    public function allScopes(): self
    {
        $this->setDefault(true);
        $this->setAllPrivateChats(true);
        $this->setAllGroupChats(true);
        $this->setAllChatAdministrators(true);
        $this->setChat(true);
        $this->setChatAdministrators(true);
        $this->setChatMember(true);

        return $this;
    }

    public function setAppCommand(bool $appCommand = true): self
    {
        $this->appCommand = $appCommand;

        return $this;
    }

    public function setDefault(bool $default = true): self
    {
        $this->default = $default;

        return $this;
    }

    public function setAllPrivateChats(bool $allPrivateChats = true): self
    {
        $this->allPrivateChats = $allPrivateChats;

        return $this;
    }

    public function setAllGroupChats(bool $allGroupChats = true): self
    {
        $this->allGroupChats = $allGroupChats;

        return $this;
    }

    public function setAllChatAdministrators(bool $allChatAdministrators = true): self
    {
        $this->allChatAdministrators = $allChatAdministrators;

        return $this;
    }

    public function setChat(bool $chat = true): self
    {
        $this->chat = $chat;

        return $this;
    }

    public function setChatAdministrators(bool $chatAdministrators = true): self
    {
        $this->chatAdministrators = $chatAdministrators;

        return $this;
    }

    public function setChatMember(bool $chatMember = true): self
    {
        $this->chatMember = $chatMember;

        return $this;
    }

    public function setUserId(?int $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function setAliases(array $aliases): self
    {
        $this->aliases = $aliases;

        return $this;
    }

    public function setParameters(ParametersObject $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getOnlyOwner(): bool
    {
        return $this->only_owner;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getCommandName(): string
    {
        return $this->command_name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function getParameters(): ?ParametersObject
    {
        return $this->parameters;
    }

    public function isAppCommand(): bool
    {
        return $this->appCommand;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function isAllChatAdministrators(): bool
    {
        return $this->allChatAdministrators;
    }

    public function isAllGroupChats(): bool
    {
        return $this->allGroupChats;
    }

    public function isAllPrivateChats(): bool
    {
        return $this->allPrivateChats;
    }

    public function isChat(): bool
    {
        return $this->chat;
    }

    public function isChatAdministrators(): bool
    {
        return $this->chatAdministrators;
    }

    public function isChatMember(): bool
    {
        return $this->chatMember;
    }
}

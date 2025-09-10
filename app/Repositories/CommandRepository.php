<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\BotCommand;
use App\Models\Command;
use Illuminate\Support\Collection;

class CommandRepository extends Repository
{

    public static array $availableScopes = [
        'default',
        'all_private_chats',
        'all_group_chats',
        'all_chat_administrators',
        'chat',
        'chat_administrators',
        'chat_member',
    ];

    public static array $chatTypes = [
        'private' => ['all_private_chats', 'chat', 'chat_member'],
        'group' => ['all_group_chats'],
        'supergroup' => ['all_group_chats', 'all_chat_administrators', 'chat', 'chat_member'],
        'channel' => ['all_chat_administrators', 'chat']
    ];

    public function __construct()
    {
        parent::__construct(Command::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function byNameOrId(
        int|string $command
    ): ?Command
    {
        return $this->db
            ->where('name', $command)
            ->orWhere('id', $command)
            ->first();
    }

    public function appCommands(bool $is_group = false): Collection
    {
        $query = $this->db->newQuery();

        $query->where('app_command', true)
            ->where('all_group_chats', $is_group);

        return $query
            ->orderBy('name')
            ->get();
    }

    public function commandsToForm(int $bot_id, int $user_id, array $default_commands = []): Collection
    {
        $already = BotCommand::where('bot_id', $bot_id)->pluck('id', 'id');

        return $this->db
            ->where('bot_id', $bot_id)
            ->whereNotIn('command', $default_commands)
            ->whereNotIn('id', $already)
            ->where(function ($q) use ($user_id) {
                $q->where('user_id', $user_id)
                    ->orWhereNot('user_id', $user_id);
            })
            ->get();
    }

    public function botCommands(int $bot_id, array $scopes = ['default']): Collection
    {
        $botCommands = BotCommand::where('bot_id', $bot_id)
            ->pluck('command_id', 'command_id');

        $command = Command::query();
        $command->where('app_command', false);
        $command->where(function ($sub) use ($scopes, $botCommands) {
            $sub->orWhereIn('id', $botCommands)
                ->orWhere(function ($whereScope) use ($scopes) {
                    foreach ($scopes as $scope) {
                        if (!in_array($scope, self::$availableScopes)) continue;

                        $whereScope->orWhere($scope, true);
                    }
                });
        });


        return $command->get();
    }

    public function defaults(): Collection
    {
        return $this->db
            ->where('default', true)
            ->get();
    }

    public function available(?int $user_id = null, array $scopes = []): Collection
    {
        $query = $this->db->newQuery();

        $query->where(function ($whereScope) use ($scopes) {
            foreach ($whereScope as $scope) {
                if (!in_array($scope, self::$availableScopes)) continue;

                $whereScope->orWhere($scope, true);
            }

            $whereScope->orWhere('default', true);
        });

        $query->where(function ($query) use ($user_id, $scopes) {
            if (!empty($user_id))
                $query->where('user_id', $user_id);

            if (empty($user_id) || in_array('command_without_user', $scopes))
                $query->orWhereNull('user_id');
        });

        return $query->orderBy('name')
            ->get();
    }

}

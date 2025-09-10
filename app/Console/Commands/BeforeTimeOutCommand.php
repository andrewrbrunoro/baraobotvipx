<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\ChatMember;
use App\Models\Remarketing;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use App\Services\Messengers\Telegram\Support\CallbackData;
use Illuminate\Console\Command;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;

class BeforeTimeOutCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:before-time-out-command {seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa o comando antes do tempo acabar';

    /**
     * Execute the console command.
     */
    public function handle()
    {

    }
}

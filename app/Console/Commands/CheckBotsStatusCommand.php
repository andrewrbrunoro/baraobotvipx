<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Order;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use Illuminate\Console\Command;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Keyboard\Keyboard;
use Carbon\Carbon;

class CheckBotsStatusCommand extends Command
{
    protected $signature = 'bots:check-status {--minutes=15 : Minutes to check for absence of orders}';
    
    protected $description = 'Verifica o status do bot principal baseado na ausência de pedidos e gerencia tentativas';

    public function handle()
    {
        $minutes = $this->option('minutes');
        $principalBot = Bot::where('principal', 1)->where('status', 'active')->first();

        if (!$principalBot) {
            $this->error('Nenhum bot principal ativo encontrado.');
            return;
        }

        $this->info("Verificando bot principal: {$principalBot->username}");

        // Verifica se há pedidos recentes
        if ($this->hasRecentOrders($principalBot, $minutes)) {
            $this->handleBotWithOrders($principalBot);
        } else {
            $this->handleBotWithoutOrders($principalBot, $minutes);
        }

        $this->info('Verificação de bots concluída.');
    }

    protected function hasRecentOrders(Bot $bot, int $minutes): bool
    {
        $lastOrder = Order::where('bot_id', $bot->id)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->first();

        return $lastOrder !== null;
    }

    protected function handleBotWithOrders(Bot $bot): void
    {
        // Bot teve pedidos recentes - resetar tentativas
        if ($bot->tries > 0) {
            $this->info("Bot {$bot->username} voltou a ter pedidos. Alarme falso detectado - resetando tentativas.");
            $this->sendFalseAlarmAlert($bot);
        }

        $bot->update([
            'tries' => 0,
            'last_try' => null
        ]);

        $this->info("Bot {$bot->username} com pedidos recentes. Tentativas resetadas.");
    }

    protected function handleBotWithoutOrders(Bot $bot, int $minutes): void
    {
        // Verifica se já notificou nesta tentativa
        $shouldNotify = $this->shouldNotifyForAttempt($bot, $minutes);
        
        $newTries = $bot->tries + 1;
        
        $bot->update([
            'tries' => $newTries,
            'last_try' => now()
        ]);

        $this->warn("Bot {$bot->username} sem pedidos por {$minutes} minutos. Tentativa {$newTries}/3");

        if ($shouldNotify) {
            $this->sendAlert($bot, $newTries, $minutes);
        }

        if ($newTries >= 3) {
            $this->handleMaxTriesReached($bot);
        }
    }

    protected function shouldNotifyForAttempt(Bot $bot, int $minutes): bool
    {
        // Só notifica se não notificou recentemente (evita spam)
        if (!$bot->last_try) {
            return true;
        }

        $lastTry = Carbon::parse($bot->last_try);
        return $lastTry->diffInMinutes(now()) >= $minutes;
    }

    protected function handleMaxTriesReached(Bot $bot): void
    {
        $this->error("Bot {$bot->username} atingiu o máximo de tentativas. Desativando...");

        // Desativa o bot atual
        $bot->update([
            'status' => 'inactive',
            'principal' => 0
        ]);

        // Busca o próximo bot ativo
        $nextBot = Bot::where('status', 'active')
            ->where('id', '>', $bot->id)
            ->orderBy('id')
            ->first();

        if ($nextBot) {
            $nextBot->update(['principal' => 1]);
            $this->info("Bot {$nextBot->username} definido como novo principal.");
            $this->sendPrincipalChangeAlert($bot, $nextBot);
        } else {
            $this->error('Nenhum bot ativo encontrado para substituir o principal.');
            $this->sendNoReplacementAlert($bot);
        }
    }

    protected function sendAlert(Bot $bot, int $tries, int $minutes): void
    {
        $lastTryText = $bot->last_try ? Carbon::parse($bot->last_try)->format('d/m/Y H:i') : 'Nunca';
        
        $message = "⚠️ *ALERTA: Bot Principal sem pedidos*\n\n";
        $message .= "[https://t.me/{$bot->username}](🤖 *Bot: {$bot->username}*)\n";
        $message .= "*Tentativa:* {$tries}/3\n";
        $message .= "*Sem pedidos por:* {$minutes} minutos\n";
        $message .= "*Última tentativa:* {$lastTryText}\n\n";
        $message .= "Não teve pedidos durante {$minutes} minutos, tentativa {$tries} ({$lastTryText})";

        $replyMarkup = Keyboard::forceReply(['selective' => false])
            ->inline()
            ->setResizeKeyboard(false)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button([
                    'text' => __('Verificar Status'),
                    'url' => 'https://baraobot.com/admin/bots',
                ]),
            ]);

        $this->sendTelegramMessage($message, $replyMarkup);
    }

    protected function sendFalseAlarmAlert(Bot $bot): void
    {
        $message = "✅ *ALARME FALSO: Bot Principal voltou a vender*\n\n";
        $message .= "[https://t.me/{$bot->username}](🤖 *Bot: {$bot->username}*)\n";
        $message .= "*Status:* Bot voltou a ter pedidos\n";
        $message .= "*Ação:* Tentativas resetadas\n\n";
        $message .= "O bot principal voltou a funcionar normalmente após período sem pedidos.";

        $replyMarkup = Keyboard::forceReply(['selective' => false])
            ->inline()
            ->setResizeKeyboard(false)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button([
                    'text' => __('Verificar Status'),
                    'url' => 'https://baraobot.com/admin/bots',
                ]),
            ]);

        $this->sendTelegramMessage($message, $replyMarkup);
    }

    protected function sendPrincipalChangeAlert(Bot $oldBot, Bot $newBot): void
    {
        $message = "🔄 *MUDANÇA DE BOT PRINCIPAL*\n\n";
        $message .= "❌ *Bot Desativado:* [https://t.me/{$oldBot->username}]({$oldBot->username})\n";
        $message .= "✅ *Novo Bot Principal:* [https://t.me/{$newBot->username}]({$newBot->username})\n\n";
        $message .= "Bot não teve vendas nas 3 tentativas, bot principal alterado de {$oldBot->username} para {$newBot->username}";

        $replyMarkup = Keyboard::forceReply(['selective' => false])
            ->inline()
            ->setResizeKeyboard(false)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button([
                    'text' => __('Gerenciar Bots'),
                    'url' => 'https://baraobot.com/admin/bots',
                ]),
            ]);

        $this->sendTelegramMessage($message, $replyMarkup);
    }

    protected function sendNoReplacementAlert(Bot $bot): void
    {
        $message = "🚨 *CRÍTICO: Nenhum bot de substituição*\n\n";
        $message .= "❌ *Bot Principal:* [https://t.me/{$bot->username}]({$bot->username})\n";
        $message .= "⚠️ *Status:* Desativado por ausência de pedidos\n";
        $message .= "❌ *Substituição:* Nenhum bot ativo encontrado\n\n";
        $message .= "ATENÇÃO: Sistema sem bot principal ativo!";

        $replyMarkup = Keyboard::forceReply(['selective' => false])
            ->inline()
            ->setResizeKeyboard(false)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button([
                    'text' => __('Ativar Bot'),
                    'url' => 'https://baraobot.com/admin/bots',
                ]),
            ]);

        $this->sendTelegramMessage($message, $replyMarkup);
    }

    protected function sendTelegramMessage(string $message, $replyMarkup = null): void
    {
        try {
            $botTelegram = BotTelegram::make(config('services.telegram_notify.bot'));
            $botTelegram->api()->sendMessage([
                'chat_id' => config('services.telegram_notify.notify_group'),
                'text' => $message,
                'parse_mode' => 'Markdown',
                'reply_markup' => $replyMarkup,
            ]);
        } catch (\Exception $e) {
            $this->error('Erro ao enviar alerta: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Jobs\OrderRemarketingJob;
use App\Models\Campaign;
use App\Models\ChatMember;
use App\Models\Order;
use App\Models\Remarketing;
use App\Models\TelegramVideo;
use App\Repositories\MemberLogRepository;
use App\Services\Messengers\Telegram\ChatTelegramManager;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use App\Services\Messengers\Telegram\Support\CallbackData;
use Illuminate\Console\Command;
use Telegram\Bot\Keyboard\Button;

class OrderRemarketingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remarketing-15-minutes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remarketing dos pedidos de 15 minutos que não foram finalizados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaign = Campaign::where('code', '15-minutos')
            ->where('is_active', true)
            ->first();

        if (!$campaign) {
            $this->error('Campanha não encontrada ou inativa');
            return;
        }

        $date = now()
            ->subSeconds($campaign->timer_seconds);

        // Busca membros que têm pedidos não finalizados no tempo definido
        $members = Order::whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') = ?", [$date->format('Y-m-d H:i')])
            ->whereIn('status', [PaymentStatusEnum::WAITING, PaymentStatusEnum::FAILURE])
            ->groupBy('member_id')
            ->pluck('member_id');

        // Busca os pedidos mais recentes desses membros
        $orders = Order::whereIn('member_id', $members)
            ->whereIn('status', [PaymentStatusEnum::WAITING, PaymentStatusEnum::FAILURE])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('member_id');

        $result = TelegramVideo::where('name', 'video_acabou')
            ->first();

        foreach ($orders as $memberOrders) {
            $order = $memberOrders->first();

            // Verifica se o pedido está em aguardando ou falhou
            if (!in_array($order->status, [PaymentStatusEnum::WAITING, PaymentStatusEnum::FAILURE])) {
                continue;
            }

            // Verifica se o usuário tem alguma assinatura ativa (não expirada)
            $hasActiveSubscription = ChatMember::where('member_id', $order->member_id)
                ->where(function($query) {
                    $query->whereNull('expired_at')
                          ->orWhere('expired_at', '>', now());
                })
                ->exists();
            
            // Se tem assinatura ativa, não envia remarketing
            if ($hasActiveSubscription) {
                continue;
            }

            OrderRemarketingJob::dispatch(
                memberId: $order->member_id,
                memberCode: $order->member->code,
                memberName: $order->member->name . ' ' . $order->member->lastname,
                botToken: $order->bot->token,
                campaign: $campaign->code
            );
        }
    }
}

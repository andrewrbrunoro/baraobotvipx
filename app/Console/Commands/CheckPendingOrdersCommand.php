<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Models\Chat;
use App\Models\ChatMember;
use App\Models\Gift;
use App\Models\Order;
use App\Objects\OrderObject;
use App\Repositories\MemberLogRepository;
use App\Services\Messengers\Telegram\ChatTelegramManager;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PushinPay\PushinPayService;
use App\Models\UserPaymentIntegration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckPendingOrdersCommand extends Command
{
    protected $signature = 'orders:check-pending';
    protected $description = 'Verifica pedidos pendentes e atualiza status via PushinPay';

    public function handle()
    {
        $this->info('Iniciando verificação de pedidos pendentes...');

        // Buscar pedidos pendentes com platform_id (PushinPay)
        $pendingOrders = Order::where('status', PaymentStatusEnum::WAITING)
            ->whereNotNull('platform_id')
            ->get();

        if ($pendingOrders->isEmpty()) {
            $this->info('Nenhum pedido pendente encontrado.');
            return;
        }

        $this->info("Encontrados {$pendingOrders->count()} pedidos pendentes.");

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($pendingOrders as $order) {
            try {
                $this->checkOrderStatus($order);
                $updatedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Erro ao verificar pedido {$order->id}: " . $e->getMessage());
                Log::error('CheckPendingOrders error', [
                    'order_id' => $order->id,
                    'platform_id' => $order->platform_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("Verificação concluída: {$updatedCount} pedidos verificados, {$errorCount} erros.");
    }

    private function checkOrderStatus(Order $order): void
    {
        // Buscar UserPaymentIntegration do usuário para PushinPay
        $userPaymentIntegration = UserPaymentIntegration::first();

        if (!$userPaymentIntegration) {
            $this->error("UserPaymentIntegration não encontrado para o pedido {$order->id}");
            return;
        }

        $paymentService = PaymentService::make($userPaymentIntegration);
        $pushinPayService = PushinPayService::make($paymentService);

        try {
            $transactionData = $pushinPayService->get($order->platform_id);
            
            if (isset($transactionData['status'])) {
                $newStatus = $this->mapPushinPayStatus($transactionData['status']);
                
                if ($newStatus !== $order->status) {
                    $order->update(['status' => $newStatus]);
                    
                    $this->info("Pedido {$order->id} atualizado: {$order->status} -> {$newStatus}");
                    
                    Log::info('Order status updated', [
                        'order_id' => $order->id,
                        'platform_id' => $order->platform_id,
                        'old_status' => $order->status,
                        'new_status' => $newStatus,
                        'transaction_data' => $transactionData
                    ]);

                    // Se o status mudou para SUCCESS, processar aprovação
                    if ($newStatus === PaymentStatusEnum::SUCCESS) {
                        $this->processOrderApproval($order);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('PushinPay transaction check failed', [
                'order_id' => $order->id,
                'platform_id' => $order->platform_id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function processOrderApproval(Order $order): void
    {
        try {
            $chatTelegram = ChatTelegramManager::make()
                ->setBot($order->bot);

            if ($order->type === 'GIFT') {
                $this->processGiftOrder($order, $chatTelegram);
            } else {
                if ($order->item_type === Chat::class) {
                    $this->processChatOrder($order, $chatTelegram);
                } else {
                    $this->processOtherOrder($order, $chatTelegram);
                }
            }

            // Marcar pedido como processado
            $order->burn = true;
            $order->save();

            $this->info("Pedido {$order->id} processado com sucesso.");

        } catch (\Exception $e) {
            Log::error('Order approval processing failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function processGiftOrder(Order $order, ChatTelegramManager $chatTelegram): void
    {
        $giftPin = Str::uuid()->toString();
        $route = 'https://t.me/VIPTUDOPUTABOT';

        Gift::firstOrCreate([
            'pin' => $giftPin,
        ], [
            'member_id' => $order->member->id,
            'product_id' => $order->product_id
        ]);

        $giftMessage = <<<HTML
        🔥 O seu pagamento foi aprovado com sucesso! 🔥

        Agora é hora de apimentar a brincadeira! 🌶️
        💌 Siga as instruções e entregue o presente de um jeito especial:

        1️⃣ Envie este link exclusivo para a pessoa que vai receber o presente: `$route`
        2️⃣ Diga para ela usar o comando mágico: /gift $giftPin

        🎁 Pronto! O clima vai esquentar ainda mais. Aproveite! 💃🕺
        HTML;

        BotTelegram::make($order->bot->token)
            ->api()
            ->sendMessage([
                'chat_id' => $order->member->code,
                'parse_mode' => 'MARKDOWN',
                'text' => $giftMessage,
            ]);

        MemberLogRepository::make()
            ->save(
                $order->member->code,
                'pagamento-finalizado',
                $order->member->name . ' ' . $order->member->lastname,
                'Pagamento finalizado com sucesso GIFT',
                json_encode(['data_finalizacao' => now()->format('d/m/Y H:i')]),
            );
    }

    private function processChatOrder(Order $order, ChatTelegramManager $chatTelegram): void
    {
        $chat = app($order->item_type)->find($order->item_id);

        $chatTelegram
            ->setChat($chat)
            ->asyncAddMember($order->member->code);

        $this->addChatMember($order);

        MemberLogRepository::make()
            ->save(
                $order->member->code,
                'pagamento-finalizado',
                $order->member->name . ' ' . $order->member->lastname,
                'Pagamento finalizado com sucesso',
                json_encode(['data_finalizacao' => now()->format('d/m/Y H:i')]),
            );
    }

    private function processOtherOrder(Order $order, ChatTelegramManager $chatTelegram): void
    {
        $chatTelegram
            ->sendMessage(
                $order->member->code,
                __('Olá, seu produto já está liberado.')
            );
    }

    private function addChatMember(Order $order): void
    {
        $alreadyMember = ChatMember::where('chat_id', $order->item_id)
            ->where('member_id', $order->member->id)
            ->where('already_kicked', 0)
            ->get();

        if (!$alreadyMember->count()) {
            ChatMember::firstOrCreate([
                'chat_id' => $order->item_id,
                'member_id' => $order->member->id,
                'already_kicked' => 0,
            ], [
                'expired_at' => now()->addSeconds($order->product->duration_time),
            ]);
        } else {
            $last = $alreadyMember->last();
            $expiredAt = $last->expired_at->addSeconds($order->product->duration_time);

            $result = $last->delete();
            if ($result) {
                ChatMember::create([
                    'chat_id' => $order->item_id,
                    'member_id' => $order->member->id,
                    'already_kicked' => 0,
                    'expired_at' => $expiredAt,
                ]);
            }
        }
    }

    private function mapPushinPayStatus(string $pushinPayStatus): string
    {
        return match (strtolower($pushinPayStatus)) {
            'paid', 'approved', 'success' => PaymentStatusEnum::SUCCESS,
            'cancelled', 'failed', 'rejected' => PaymentStatusEnum::FAILURE,
            default => PaymentStatusEnum::WAITING
        };
    }
}

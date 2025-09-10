<?php declare(strict_types=1);

namespace App\Http\Controllers\MercadoPago;

use App\Models\ChatMember;
use App\Models\Gift;
use App\Repositories\MemberLogRepository;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use Exception;
use App\Models\Chat;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Objects\OrderObject;
use App\Enums\PaymentStatusEnum;
use Illuminate\Http\JsonResponse;
use App\Enums\PaymentPlatformEnum;
use App\Http\Controllers\Controller;
use App\Models\UserPaymentIntegration;
use App\Services\Messengers\Telegram\ChatTelegramManager;
use App\Services\Payments\MercadoPago\Enums\MercadoPaymentEnum;
use App\Services\Payments\MercadoPago\Exceptions\MercadoPagoNotifyException;
use Illuminate\Support\Str;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class NotifyController extends Controller
{
    private function chatMember(Order $order): void
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

    public function notify(Request $request): JsonResponse
    {
        try {

            if (!$request->filled('id'))
                return response()->json(['message' => __('ID de pedido é obrigatório')], 422);

            $order = Order::where('platform_id', $request->get('id'))
                ->first();

            if (!$order)
                return response()->json(['message' => __('Pedido não encontrado.')]);
            else if ($order->burn) {
                return response()->json([
                    'message' => __('Pedido já foi finalizado')
                ]);
            }

            MercadoPagoConfig::setAccessToken(
                env('MERCADOPAGO_ACCESS_TOKEN')
            );

            $mp = new PaymentClient();
            $result = $mp->get((int) request('id'));

            $mpStatus = $result->status;

            $status = MercadoPaymentEnum::status($mpStatus);

            $order->status = $status;
            $result = $order->save();
            if (!$result) {
                return response()
                    ->json([
                        'message' => __('Falha ao salvar o status do pedido')
                    ], 400);
            }

            if ($status === PaymentStatusEnum::SUCCESS) {

                $chatTelegram = ChatTelegramManager::make()
                    ->setBot($order->bot);

                if ($order->type === 'GIFT') {

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
                            json_encode(request()->all()),
                        );

                } else {
                    if ($order->item_type === Chat::class) {
                        $chat = app($order->item_type)
                            ->find($order->item_id);

                        info('=> NotifyController 150: ', [
                            'chat' => $chat,
                            'order' => $order,
                            'member' => $order->member,
                        ]);

                        $chatTelegram
                            ->setChat($chat)
                            ->asyncAddMember(
                                $order->member->code,
                            );

                        $this->chatMember($order);

                        $order->burn = true;
                        $order->save();

                        MemberLogRepository::make()
                            ->save(
                                $order->member->code,
                                'pagamento-finalizado',
                                $order->member->name . ' ' . $order->member->lastname,
                                'Pagamento finalizado com sucesso',
                                json_encode([
                                    'data_finalizacao' => now()->format('d/m/Y H:i'),
                                    ...request()->all(),
                                ]),
                            );

                    } else {
                        $chatTelegram
                            ->sendMessage(
                                $order->member->code,
                                __('Olá, seu produto já está liberado.')
                            );
                    }
                }

                // $apiTelegram = BotTelegram::make(config('app.custom_bot'));

                // $total = $order->price_sale > 0
                //     ? $order->price_sale
                //     : $order->price;

                // $apiTelegram
                //     ->api()
                //     ->sendMessage([
                //         'text' => <<<HTML
                //         💰🤑🎉 Pagamento finalizado com sucesso 🎉💰🤑

                //         📋 Pedido <b>#$order->id</b>
                //         🎫 Total: <b>$total</b>
                //         HTML,
                //         'parse_mode' => 'HTML',
                //         'chat_id' => '5822905454'
                //     ]);
            }

            return response()
                ->json(['message' => __('Notificado')]);
        } catch (MPApiException $e) {
            return response()->json([
                'message' => $e->getApiResponse(),
            ], 400);
        }
    }

    public function approved(Request $request): View
    {
        try {

            info('approved');
            info(print_r($request->all(), true));

            $orderObject = $this->getOrder($request->all());

            $orderObject->addLog(
                PaymentPlatformEnum::MERCADO_PAGO,
                $request->all(),
                "notify.approved"
            );

            $order = $orderObject->getOrder();
            if (
                $order->status === MercadoPaymentEnum::status('approved')
                || $order->burn
            ) {
                return view('payment.success');
            }

            $chatTelegram = ChatTelegramManager::make()
                ->setBot($order->bot);

            if ($order->item_type === Chat::class) {
                $chat = app($order->item_type)->find($order->item_id);

                if ($order->type === 'GIFT') {

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
                    2️⃣ Diga para ela usar o comando: `/gift $giftPin`

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
                            json_encode(request()->all()),
                        );
                } else {

                    $chatTelegram
                        ->setChat($chat)
                        ->asyncAddMember(
                            $order->member->code,
                        );

                    $this->chatMember($order);

                    $apiTelegram = BotTelegram::make(config('app.custom_bot'));

                    $total = $order->price_sale > 0
                        ? $order->price_sale
                        : $order->price;

                    $apiTelegram
                        ->api()
                        ->sendMessage([
                            'text' => <<<HTML
                            💰🤑🎉 Pagamento finalizado com sucesso 🎉💰🤑

                            📋 Pedido <b>#$order->id</b>
                            🎫 Total: <b>$total</b>
                            HTML,
                            'parse_mode' => 'HTML',
                            'chat_id' => '5822905454'
                        ]);

                    MemberLogRepository::make()
                        ->save(
                            $order->member->code,
                            'pagamento-finalizado',
                            $order->member->name . ' ' . $order->member->lastname,
                            'Pagamento finalizado com sucesso',
                            json_encode(request()->all()),
                        );
                }

            } else {
                $chatTelegram
                    ->sendMessage(
                        $order->member->code,
                        __('Olá, seu produto já está liberado.')
                    );
            }

            $orderObject
                ->changeStatus(PaymentStatusEnum::SUCCESS);

            $orderObject
                ->burn();

            return view('payment.success');
        }  catch (Exception $e) {
            report($e);
            return view('payment.error');
        }
    }

    public function pending(Request $request): JsonResponse
    {
        try {

            $orderObject = $this->getOrder($request->all());

            $orderObject->addLog(
                PaymentPlatformEnum::MERCADO_PAGO,
                $request->all(),
                "notify.pending"
            );

            $order = $orderObject->getOrder();
            if ($order->status === MercadoPaymentEnum::status('pending')) {
                return response()->json([
                    'message' => __('Mandamos um lembre para o usuário!')
                ]);
            }

            $chatTelegram = ChatTelegramManager::make()
                ->setBot($order->bot);

            $chatTelegram
                ->sendMessage(
                    $order->member->code,
                    <<<HTML
                    Olá! 🙋‍♂️ O seu pedido está pendente.

                    ⏳ Estamos aguardando a confirmação do pagamento pelo nosso sistema.

                    💳 Assim que houver uma atualização, avisaremos!
                    HTML
                );

            $orderObject
                ->changeStatus(PaymentStatusEnum::WAITING);

            return response()->json([
                'message' => __('Notificação recebida com sucesso!'),
            ]);
        } catch (Exception $e) {
            report($e);
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function failure(Request $request): JsonResponse
    {
        try {

            $orderObject = $this->getOrder($request->all());

            $orderObject->addLog(
                PaymentPlatformEnum::MERCADO_PAGO,
                $request->all(),
                "notify.failure"
            );

            $order = $orderObject->getOrder();
            if ($order->status === MercadoPaymentEnum::status('failure')) {
                return response()->json([
                    'message' => __('Usuário já foi notificado!')
                ]);
            }

            $chatTelegram = ChatTelegramManager::make()
                ->setBot($order->bot);

            $chatTelegram
                ->sendMessage(
                    $order->member->code,
                    <<<HTML
                    Olá! 🙋‍♂️ Informamos que, infelizmente, o seu pedido foi negado. 🚫 Sentimos muito por isso! 😔

                    Mas não se preocupe, você pode refazer o pedido a qualquer momento digitando /products.
                    HTML
                );

            $orderObject
                ->changeStatus(PaymentStatusEnum::FAILURE);

            return response()->json([
                'message' => __('Notificação recebida com sucesso!'),
            ]);
        } catch (Exception $e) {
            report($e);
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }


    private function getOrder(array $data): OrderObject
    {
        $orderId = $data['external_reference'] ?? null;
        if (!$orderId)
            throw new MercadoPagoNotifyException('Referencia externa não foi fornecida.', 422);

        $order = Order::where('uuid', $orderId)
            ->orWhere('platform_id', $orderId)
            ->first();
        if (!$order)
            throw new MercadoPagoNotifyException('Pedido não encontrado.', 422);

        return OrderObject::make(
            $order->user_id,
            $order->bot,
            $order->member,
            new UserPaymentIntegration(),
        )
            ->setOrder($order);
    }
}

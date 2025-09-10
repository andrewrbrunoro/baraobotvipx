<?php declare(strict_types=1);

namespace App\Http\Controllers\PushinPay;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotifyController extends Controller
{
    public function notify(Request $request)
    {
        // Log completo da requisição
        Log::info('PushInPay Webhook Received', [
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'query_params' => $request->query(),
            'body' => $request->all(),
            'raw_body' => $request->getContent(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Log específico para GET requests
        if ($request->isMethod('get')) {
            Log::info('PushInPay GET Webhook', [
                'query_params' => $request->query(),
                'all_params' => $request->all(),
            ]);
        }

        // Log específico para POST requests
        if ($request->isMethod('post')) {
            Log::info('PushInPay POST Webhook', [
                'json_data' => $request->json()->all(),
                'form_data' => $request->all(),
                'content_type' => $request->header('Content-Type'),
            ]);
        }

        // Processar notificação se tiver dados válidos
        $data = $request->all();
        
        if (empty($data)) {
            Log::warning('PushInPay Webhook: Empty data received');
            return response()->json(['status' => 'error', 'message' => 'No data received'], 400);
        }

        // Tentar encontrar o pedido pelo ID da transação
        $transactionId = $data['id'] ?? null;
        
        if ($transactionId) {
            $order = Order::where('platform_id', $transactionId)->first();
            
            if ($order) {
                Log::info('PushInPay Webhook: Order found', [
                    'order_id' => $order->id,
                    'order_uuid' => $order->uuid,
                    'transaction_id' => $transactionId,
                    'order_status' => $order->status,
                ]);
                
                // Aqui você pode adicionar a lógica de processamento do pagamento
                // baseado no status recebido do PushInPay
                
            } else {
                Log::warning('PushInPay Webhook: Order not found', [
                    'transaction_id' => $transactionId,
                ]);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Webhook received']);
    }
}
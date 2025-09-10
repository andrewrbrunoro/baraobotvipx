<?php

use App\Filament\Resources\BotResource;
use App\Http\Controllers\MercadoPago\NotifyController;
use App\Http\Controllers\PushinPay\NotifyController as PushinPayNotifyController;
use App\Http\Controllers\RedirectPlatformController;
use App\Http\Controllers\TelegramController;
use App\Services\Messengers\Telegram\ChatTelegramManager;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShortRedirectController;
use App\Http\Controllers\BotErrorController;
use App\Http\Controllers\BotRedirectController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\UserTelegramController;


function uploadVideo ($token, $chat_id, $video, $name) {
    $result = BotTelegram::make($token)
        ->api()
        ->sendVideo([
            'chat_id' => $chat_id,
            'video' => \Telegram\Bot\FileUpload\InputFile::create(public_path('videos/' . $video))
        ]);

    dump($result);
    $video = $result->video;
    dump($video);

    \App\Models\TelegramVideo::where('name', $name)
        ->update([
            'telegram_id' => $video->fileId
        ]);
}

function menu($token)
{
    BotTelegram::make($token)
        ->webhook()
        ->set(BotResource::getWebhookUrl($token))
        ->manageCommand()
        ->setCommands([
            \App\Services\Messengers\Telegram\Commands\HelpCommand::class,
            \App\Services\Messengers\Telegram\Commands\StatusCommand::class,
            \App\Services\Messengers\Telegram\Commands\ProductListCommand::class,
        ])
//        ->appBotCommands()
        ->toMenu();

    BotTelegram::make($token)
        ->webhook()
        ->set(BotResource::getWebhookUrl($token))
        ->manageCommand()
        ->setCommands([
            \App\Services\Messengers\Telegram\Commands\HelpCommand::class,
            \App\Services\Messengers\Telegram\Commands\StatusCommand::class,
            \App\Services\Messengers\Telegram\Commands\ProductListCommand::class,
        ], 'pt')
//        ->appBotCommands()
        ->toMenu();
}

Route::get('/bot-webhook/{token}', function () {
    $token = request()->route('token');

    $result = BotTelegram::make($token)
        ->webhook()
        ->set(BotResource::getWebhookUrl($token));

    dd($result);

    return response()
        ->json([
            'message' => 'Done',
        ]);
});


Route::get('/bot-menu/{token}', function () {
    $token = request()->route('token');

    menu($token);

    return response()
        ->json([
            'message' => 'Done',
        ]);
});


Route::get('/bot-error/{token}/webhook', function () {
    try {
        $token = request()->route('token');
        //-> adiciona o menu e o webhook
        menu($token);

        $old = \App\Models\Bot::where('id', 7)
            ->first();

        //-> cria um backup
        $oldArray = $old->toArray();
        unset($oldArray['id'], $oldArray['created_at'], $oldArray['updated_at']);

        \App\Models\Bot::create($oldArray);

//        $old->token = $token;

        return response()
            ->json([
                'message' => 'Done',
            ]);
    } catch (Exception $e) {
        dd($e);
    }
});

Route::get('/bot-error/{token}/menu', [BotErrorController::class, 'setupMenu']);
Route::get('/bot-error/{token}/videos', [BotErrorController::class, 'setupBot']);
Route::get('/bot/mainly', BotRedirectController::class);

Route::get('/revoke-link', function () {

//
    $result = BotTelegram::make(config('app.custom_bot'))
        ->api()
        ->revokeChatInviteLink([
            'chat_id' => '-1001972835479',
            'invite_link' => 'https://t.me/+2btBmqEQemwxZGUx',
        ]);

    dd($result);
});

Route::get('/test', function () {

    $today = now();
    $expire = '2024-11-09 23:23:30';
    $expire = '2024-12-04 08:50:00';

    dd($today->diffInSeconds($expire));

});

Route::get('/', LandingPageController::class)->name('lp.index');
Route::post('/contact', [LandingPageController::class, 'contact'])->name('lp.contact');

Route::get('/test', function () {
    try {

        $result = BotTelegram::make(config('app.custom_bot'))
            ->api()
            ->createChatInviteLink([
                'chat_id' => '-1002286003027',
                'user_id' => '6138872863',
                'creates_join_request' => true,
            ]);

        dd($result->invite_link);

//        BotTelegram::make('7817101223:AAHDgI6hh3fmoRSFZJmQ6DDUTppZfcVwmKE')
//            ->api()
//            ->sendMessage([
//                'chat_id' => -1002286003027,
//                'text' => 'Hello World!'
//            ]);
    } catch (Exception $e) {
        dd($e);
    }
});

Route::get('/test-video', function () {

//    'video_acabou' => 'BAACAgEAAxkDAAMKZ0-jBeYhnG-7cIY1QKAkN4-kfQMAAj8GAAIy-4FGnpVV_DpHoFw2BA',
//    'start' => 'BAACAgEAAxkDAAMLZ0-jWvpBSGvcLurx25q10lMISVoAAkAGAAIy-4FGZUTOxvISt_A2BA',
//    'request_join_accepted' => 'BAACAgEAAxkDAAIBqmdRxzBFmDBCZLJW_FjGTwQuOOCUAAIsBQACVkORRqNlZgLjoU98NgQ',
//    'gift_video' => 'BAACAgEAAxkDAAIf7GdVxo-ZeTfenipqnkj7f6anfzLsAAL9BAACiIGxRjYwB6UQt-P-NgQ',



//    $telegram = BotTelegram::make('7417512977:AAEKlBI8Ld3fT7RmlSZmdaJKjJsmvp_sz4c')
//        ->api()
//        ->sendVideo([
//            'chat_id' => '5822905454',
//            'caption' => "Aqui está o seu vídeo: teste",
//            'video' => "BAACAgEAAxkDAANyZ0ZiVoiiNixpCVPFqY07lqgBXGoAAoADAAK1BDhGEtGNlKvqPPI2BA"
//        ]);

    $videos = [
        'start.mp4',
        'video_acabou.mp4',
        'ANNY.mp4',
        'urach.mp4',
    ];

    $token = '8032472119:AAEZpzTpSd9akYF9FG788J33H_qwcRQZVFE';

    foreach ($videos as $video) {
        $result = BotTelegram::make($token)
            ->api()
            ->sendVideo([
                'chat_id' => '5822905454',
                'video' => \Telegram\Bot\FileUpload\InputFile::create(public_path('videos/' . $video))
            ]);

        dump($video);
        dump($result);
    }

    exit;

//    "duration" => 12
//      "width" => 480
//      "height" => 854
//      "file_name" => "novo 480.mp4"
//      "mime_type" => "video/mp4"
//      "thumbnail" => array:5 [▶]
//      "thumb" => array:5 [▶]
//      "file_id" => "BAACAgEAAxkDAAOpZ0ZsIkLY-Msk5ojPtuaOLy4dwhYAAoEDAAK1BDhG-I3Ep1JiixY2BA"
//      "file_unique_id" => "AgADgQMAArUEOEY"
//      "file_size" => 5295688
});

Route::get('/test-payment', function () {
    return view('payment.success');
});

Route::get('/telegram/oauth', \App\Http\Controllers\Oauth\TelegramOauthController::class);

Route::get('/telegram/{owner_code}/sync', [TelegramController::class, 'sync'])
    ->name('telegram.sync')
    ->middleware([
        'throttle:5,1',
        \Filament\Http\Middleware\Authenticate::class,
    ]);

Route::post('/telegram/{token}/webhook', [TelegramController::class, 'chat'])
    ->name('webhook.telegram.chat');

Route::middleware('throttle:2,1')
    ->get('/telegram/{hash}/approve-bot', [TelegramController::class, 'approveBot'])
    ->name('telegram.approve.bot');

Route::get('/short/{code}', ShortRedirectController::class)
    ->middleware('throttle:2,1')
    ->name('shortUri');

Route::get('/redirect/mercado-pago', [RedirectPlatformController::class, 'mercadoPago']);

Route::post('/mercado-pago/notify', [NotifyController::class, 'notify']);
Route::any('/mercado-pago/notify/success', [NotifyController::class, 'approved']);
Route::any('/mercado-pago/notify/approved', [NotifyController::class, 'approved']);
Route::any('/mercado-pago/notify/failure', [NotifyController::class, 'failure']);
Route::any('/mercado-pago/notify/pending', [NotifyController::class, 'pending']);

// PushInPay Webhook Routes
Route::get('/pushinpay/webhook', [PushinPayNotifyController::class, 'notify']);
Route::post('/pushinpay/webhook', [PushinPayNotifyController::class, 'notify']);

// QR Code Image Route
Route::get('/qrcode/{orderId}', function ($orderId) {
    $order = \App\Models\Order::find($orderId);
    
    if (!$order) {
        abort(404, 'Order not found');
    }
    
    return view('qrcode', compact('order'));
})->name('qrcode.image');


Route::get('/{id}/success-payment', function () {

    $order = \App\Models\Order::find(request('id'));
    if (!$order)
        abort('Pedido não encontrado.');

    $chat = app($order->item_type)->find($order->item_id);
    if (!$chat)
        abort('Chat não encontrado.');

    $product = \App\Models\Product::find($order->product_id);
    if (!$product)
        abort('Produto não encontrado.');

    $dataDurationTime = (int)$product->duration_time;

    if ($dataDurationTime === 0) {
        $durationTime = today()->clone()->addYears(5);
    } else {
        $durationTime = today()->clone()->addSeconds($dataDurationTime);
    }

    \App\Models\ChatMember::updateOrCreate([
        'chat_id' => $chat->id,
        'member_id' => $order->member_id,
    ], [
        'expired_at' => $durationTime,
    ]);

    $bot = $order->bot;
    $member = $order->member;

    $chatTelegramManager = ChatTelegramManager::make()
        ->setBot($bot)
        ->setChat($chat);

    $result = $chatTelegramManager
        ->addMember([
            'user_id' => $member->code,
            'chat_id' => $chat->code,
            'text' => $data['text'] ?? null
        ]);

    if (!$result)
        abort(400);

    die('você foi adicionado no grupo');

})->name('order.success');

Route::get('/add-member', function () {
    try {
        $chat = \App\Services\Messengers\Telegram\ChatTelegramManager::make()
            ->setChat(\App\Models\Chat::where('id', 2)->first())
            ->setBot(\App\Models\Bot::where('id', 2)->first());

        $chat->asyncAddMember(689161503);
    } catch (Exception $e) {
        dd($e);
    }

    return response()->json([
        'message' => 'add'
    ]);
});

Route::get('/remove-member', function () {

    $chat = \App\Services\Messengers\Telegram\ChatTelegramManager::make()
        ->setChat(\App\Models\Chat::first())
        ->setBot(\App\Models\Bot::first());

    $chat->asyncRemoveMember(689161503);
//    $chat->asyncRemoveMember(2042430793);

    return response()->json([
        'message' => 'remove'
    ]);
});

Route::get('/test-telegram-notify', function () {
    $message = "Teste de notificação do bot - " . now()->format('d/m/Y H:i:s');
    
    $botTelegram = BotTelegram::make(config('services.telegram_notify.bot'));
    $botTelegram->api()->sendMessage([
        'chat_id' => config('services.telegram_notify.notify_group'),
        'text' => $message,
        'parse_mode' => 'Markdown',
    ]);

    return response()->json([
        'message' => 'Notificação enviada com sucesso!',
        'sent_message' => $message
    ]);
});

<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Repositories\AuthPinRepository;
use App\Repositories\BotRepository;
use App\Services\Messengers\ApproveBot;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramController extends Controller
{
    private BotRepository $botRepository;

    private AuthPinRepository $authPinRepository;

    public function __construct()
    {
        $this->botRepository = new BotRepository();
        $this->authPinRepository = new AuthPinRepository();
    }

    public function sync(string $owner_code): RedirectResponse
    {
        $user = auth()->user();
        $notification = Notification::make();

        if (!empty($user->telegram_owner_code)) {
            $notification
                ->danger()
                ->body(__('Essa conta já está sincronizada com um Telegram'));
        } else {
            $notification
                ->success()
                ->body(__('Sua conta está sincronizada com o seu Telegram pessoal, você pode começar a criar os BOTs'));

            $user->telegram_owner_code = $owner_code;
            $result = $user->save();
            if (!$result)
                abort(400);
        }

        $notification
            ->send();

        return redirect('/admin');
    }

    public function approveBot(string $hash): View
    {
        $hash = decrypt($hash);
        if (!($hash instanceof ApproveBot))
            abort(404);

        $authPin = $hash->getAuthPin();

        $bot = $this->botRepository->findById($authPin->bot_id);
        if (!$bot)
            abort(404);

        //-> verifica novamente, mas garante que o token não foi expirado ou já não foi usado
        $auth = $this->authPinRepository
            ->alreadyGenerate($bot->id, $authPin->chat_id);
        if (!$auth)
            return view('telegram.approve-bot', ['message' => __('O BOT já está ativado.')]);

        $message = 'O BOT foi ativado com sucesso!';

        $auth->verified_at = now();
        $result = $auth->save();
        if (!$result)
            $message = __('Não foi possível ativar seu BOT, tente novamente, caso o erro persista entre em contato conosco.');


        $this->botRepository->updatePinVerified(
            $bot,
            $hash->getOwnerId(),
        );
        if (!$result)
            $message = __('Não foi possível ativar seu BOT, tente novamente, caso o erro persista entre em contato conosco.');

        BotTelegram::make($bot->token)
            ->api()
            ->sendMessage([
                'chat_id' => $authPin->chat_id,
                'parse_mode' => 'HTML',
                'text' => <<<HTML
                Seu BOT foi verificado com sucesso!

                Agora você pode adicionar este BOT como ADMINISTRADOR de Grupos para que ele possa gerenciar os acessos.
                HTML,
                'reply_markup' => Keyboard::make()
                    ->inline()
                    ->setSelective(true)
                    ->row([
                        Button::make()
                            ->setText('Gerenciar Grupo')
                            ->setUrl(sprintf('https://t.me/%s?startgroup=start', $bot->username))
                    ])
            ]);

        return view('telegram.approve-bot', compact('message'));
    }

    public function chat(string $token): JsonResponse
    {
        try {

            $bot = $this->botRepository->findByToken($token);
            if (!$bot && $token !== env('APP_BOT_TELEGRAM_TOKEN'))
                return response()->json(['message' => 'Bot não encontrado.'], 404);

            BotTelegram::make($token)
                ->setupMember()
                ->setupCommands()
                // ->setupSpecificCommandsToMenu([
                //     [
                //         'command' => 'plans',
                //         'description' => 'All plans available'
                //     ],
                //     [
                //         'command' => 'status',
                //         'description' => 'Check your subscribe status'
                //     ]
                // ], ['en'])
                ->setupSpecificCommandsToMenu([
                    [
                        'command' => 'planos',
                        'description' => 'Todos os planos disponíveis'
                    ],
                    [
                        'command' => 'status',
                        'description' => 'Verifique a situação da sua assinatura'
                    ]
                ])
                ->run();

        } catch (\Exception $e) {
            report($e);
            info('=> erro telegramcontroller 120: ', [
                'message' => $e->getMessage(),
                'token' => $token,
                'bot' => request()->all(),
            ]);
        }

        return response()->json([
            'message' => __('Mensagem recebida com sucesso!')
        ]);
    }

}

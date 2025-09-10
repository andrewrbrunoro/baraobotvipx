<?php

namespace App\Http\Controllers;

use App\Models\Member;
use danog\MadelineProto\EventHandler\Keyboard\InlineKeyboard;
use Illuminate\Http\Request;
use danog\MadelineProto\API;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\Settings;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class UserTelegramController extends Controller
{
    private $madelineProto;
    private $sessionFile;

    public function __construct()
    {
        $this->sessionFile = storage_path('app/madeline.andrew.session');;

        $settings = new Settings;
        $telegramHash = 'd2b7e9d896363d0535dc04f1ec6841de';
        $telegramApiId = 20412638;

        $settings->getAppInfo()
            ->setApiId($telegramApiId)
            ->setApiHash($telegramHash);

        $this->madelineProto = new API($this->sessionFile, $settings);
    }

    public function index()
    {
        return view('telegram.index');
    }

    public function connect(Request $request)
    {
        try {
            if ($request->has('getQrCode')) {
                $this->madelineProto->start();
                return response()->json([
                    'logged_in' => false,
                    'message' => 'Verifique seu Telegram para o código de autenticação'
                ]);
            }

            if ($request->has('waitQrCodeOrLogin')) {
                if ($this->madelineProto->getSelf()) {
                    return response()->json([
                        'logged_in' => true
                    ]);
                }
                return response()->json([
                    'logged_in' => false
                ]);
            }

            if (!$this->madelineProto->getSelf()) {
                $this->madelineProto->start();
                return response()->json([
                    'success' => true,
                    'message' => 'Verifique seu Telegram para o código de autenticação'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Já está conectado!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getMessages(Request $request)
    {
        try {
            if (!$this->madelineProto->getSelf()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não está conectado'
                ], 401);
            }

            $chatId = $request->get('chat_id');

//            $messages = $this->madelineProto->channels->readMessageContents(
//                -1001972835479
//            );

//            $messages = $MadelineProto->messages->getHistory(
//                peer: $channel,
//                limit: 10, // Número de mensagens a recuperar (máx. 100 por chamada)
//                offset_id: 0, // ID da mensagem inicial (0 para as mais recentes)
//                offset_date: 0, // Data de offset (0 para ignorar)
//                add_offset: 0, // Offset adicional
//                max_id: 0, // ID máximo (0 para ignorar)
//                min_id: 0, // ID mínimo (0 para ignorar)
//                hash: 0 // Hash para paginação (0 para ignorar)
//            );

//            $messages = $this->madelineProto->channels->getParticipants(
//                filter: ['_' => 'channelParticipantsRecent'],
//                channel: $chatId, // Filtro (recentes, admins, etc.)
//                offset: 200,
//                limit: 200
//            );

            $channel = '-100123456789'; // Substitua pelo ID do canal (ex.: -100123456789)
            $limit = 200; // Máximo permitido por chamada
            $offset = 0;
            $allParticipants = [];

// Loop para buscar todos os participantes
            do {
                try {
                    // Faz a chamada para obter participantes
//                    $participants = $this->madelineProto->channels->getParticipants(
//                        channel: $chatId,
//                        filter: ['_' => 'channelParticipantsSearch', 'q' => ''], // Busca todos os membros
//                        offset: $offset,
//                        limit: $limit,
//                        hash: 0
//                    );

                    $test = $this->madelineProto->getPwrChat(
                        id: $chatId,
                    );
//
                    dd($test);

                    // Verifica se há participantes na resposta
                    if (!isset($participants['participants']) || empty($participants['participants'])) {
                        break; // Sai do loop se não houver mais participantes
                    }

                    // Adiciona os participantes ao array
                    $allParticipants = array_merge($allParticipants, $participants['participants']);

                    // Atualiza o offset para a próxima página
                    $offset += count($participants['participants']);

                    // Adiciona um pequeno atraso para evitar flood
                    usleep(200000); // 0.1 segundo de pausa entre chamadas

                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    // Trata erros, como flood wait
                    dd('stop');
                    if (strpos($e->getMessage(), 'FLOOD_WAIT') !== false) {
                        $waitTime = (int) preg_replace('/[^0-9]/', '', $e->getMessage());
                        echo "Aguardando $waitTime segundos devido a limite de taxa...\n";
                        sleep($waitTime);
                        continue;
                    }
                    echo "Erro: " . $e->getMessage() . "\n";
                    break;
                }
            } while (true);

            dump(count($allParticipants));

            $codes = array_map(fn($item) => $item['user_id'], $allParticipants);
            dump($codes, count($codes), array_unique($codes), count(array_unique($codes)));

            $member = Member::whereIn('code', $codes)
                ->has('chatMember')
                ->groupBy('code')
                ->get();

            dump($member->count(), $member->pluck('code'));

            $member = Member::whereIn('code', $codes)
                ->whereDoesntHave('chatMember')
                ->groupBy('code')
                ->get();

            dd($member->count(), $member->pluck('code'));

//            $messages = $this->madelineProto->messages->getHistory([
//                'peer' => $chatId,
//                'limit' => 50
//            ]);

            return response()->json([
                'success' => true,
                'messages' => $messages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    function resolveShortUrl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Seguir redirecionamentos
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

        $response = curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // Obtém a URL final
        curl_close($ch);

        return $finalUrl;
    }

    public function getChats()
    {
        try {
            if (!$this->madelineProto->getSelf()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não está conectado'
                ], 401);
            }

            $dialogs = $this->madelineProto->getFullDialogs();
            $chats = [];

            foreach ($dialogs as $dialog) {
                $peer = $dialog['peer'];
                $chat = $this->madelineProto->getInfo($peer);

//                if (isset($chat['User']) && ($chat['User']['deleted'] || !$chat['User']['contact'])) {
//                    continue;
//                }

                $chats[] = [
                    'id' => $chat['User']['id'] ?? $chat['Chat']['id'] ?? null,
                    'title' => $chat['User']['first_name'] ?? $chat['Chat']['title'] ?? 'Chat sem nome',
                    'type' => $chat['type'] ?? 'unknown'
                ];
            }

//            foreach ($chats as $item) {
//                if (str_contains($item['title'], 'xet')) {
//                    dd($item);
//                }
//            }

            return response()->json([
                'success' => true,
                'chats' => $chats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        try {
            if (!$this->madelineProto->getSelf()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não está conectado'
                ], 401);
            }

            $chatId = $request->get('chat_id');
            $message = $request->get('message');

            if (!$chatId || !$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat ID e mensagem são obrigatórios'
                ], 400);
            }

            $result = $this->madelineProto->messages->sendMessage(
                silent: false, // Não enviar silenciosamente
                background: false, // Não enviar como mensagem de fundo
                clear_draft: false, // Não limpar rascunho
                noforwards: false, // Permitir encaminhamento
                peer: '@me',
                message: 'Com Botão',
                reply_markup: [
                    '_' => 'replyInlineMarkup',
                    'rows' => [
                        [
                            '_' => 'keyboardButtonRow',
                            'buttons' => [
                                [
                                    '_' => 'keyboardButtonCallback',
                                    'text' => 'Clique Aqui',
                                    'data' => 'callback_data_123' // Dados enviados no callback
                                ]
                            ]
                        ]
                    ]
                ],
                parse_mode: \danog\MadelineProto\ParseMode::HTML,
            );

            return response()->json([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

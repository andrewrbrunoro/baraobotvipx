<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotChat;
use App\Models\TelegramVideo;
use App\Repositories\BotRepository;
use App\Services\Messengers\Telegram\Commands\HelpCommand;
use App\Services\Messengers\Telegram\Commands\StatusCommand;
use App\Services\Messengers\Telegram\Commands\ProductListCommand;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use App\Filament\Resources\BotResource;
use Illuminate\Http\JsonResponse;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\FileUpload\InputFile;

class BotErrorController extends Controller
{

    protected int $userId = 1;

    protected int $channelId = 1;

    protected string $chatAndrew = '689161503';

    protected string $chatID = '5822905454';

    public function __construct(
        protected BotRepository $botRepository
    ) {}

    protected function uploadVideo(string $token, string $chat_id, string $video, string $name): void
    {
        $result = BotTelegram::make($token)
            ->api()
            ->sendVideo([
                'chat_id' => $chat_id,
                'video' => InputFile::create(public_path('videos/' . $video))
            ]);

        $video = $result->video;

        TelegramVideo::updateOrCreate(
            [
                'name' => $name,
                'bot_token' => $token
            ],
            [
                'telegram_id' => $video->fileId
            ]
        );
    }

    public function setupBot(string $token): JsonResponse
    {
        try {
            $botTelegram = BotTelegram::make($token);
            $botInfo = $botTelegram->getMe();

            $bot = $this->botRepository->create(
                code: $botInfo->id,
                bot_data: [
                    'owner_code' => 5822905454,
                    'is_verified' => 1,
                    'token' => $token,
                    'first_name' => $botInfo->first_name,
                    'username' => $botInfo->username,
                    'can_join_groups' => 1,
                    'can_read_all_group_messages' => 0,
                    'supports_inline_queries' => 0,
                    'can_connect_to_business' => 0,
                    'has_main_web_app' => 0
                ],
                user_id: $this->userId
            );

            if (!$bot) {
                return response()->json([
                    'message' => 'Não foi possível criar o bot'
                ], 500);
            }

            // Upload dos vídeos
            $chatID = request('chat_id', $this->chatID);
            $videos = [
                'video_acabou' => 'start.mp4',
                'start' => 'start.mp4',
                'request_join_accepted' => 'start.mp4',
                'gift_video' => 'start.mp4',
            ];

            foreach ($videos as $name => $video) {
                $this->uploadVideo($token, $chatID, $video, $name);
            }

            return response()->json([
                'message' => 'Bot configurado com sucesso',
                'bot' => $bot
            ]);

        } catch (TelegramResponseException $e) {
            return response()->json([
                'message' => 'Token inválido ou erro na API do Telegram',
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao configurar o bot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function setupMenu(string $token): JsonResponse
    {
        try {
            $botTelegram = BotTelegram::make($token);
            $bot = $this->botRepository->findByToken($token);

            if (!$bot) {
                return response()->json([
                    'message' => 'Bot não encontrado'
                ], 404);
            }

            // Configura o webhook
            $botTelegram->webhook()
                ->set(BotResource::getWebhookUrl($token));

            // Configura os comandos em inglês
            $botTelegram->manageCommand()
                ->setCommands([
                    HelpCommand::class,
                    StatusCommand::class,
                    ProductListCommand::class,
                ], 'en')
                ->toMenu();

            // Configura os comandos em português
            $botTelegram->manageCommand()
                ->setCommands([
                    HelpCommand::class,
                    StatusCommand::class,
                    ProductListCommand::class,
                ], 'pt')
                ->toMenu();

            // Insere os comandos padrão
            $commands = [
                ['command_id' => 25],
                ['command_id' => 27],
                ['command_id' => 31],
                ['command_id' => 28],
            ];

            foreach ($commands as $command) {
                BotCommand::create([
                    'bot_id' => $bot->id,
                    'command_id' => $command['command_id'],
                    'chat_id' => null
                ]);
            }

            // Cria o BotChat
            BotChat::create([
                'bot_id' => $bot->id,
                'chat_id' => $this->channelId,
                'verified_by' => $this->chatID
            ]);

            return response()->json([
                'message' => 'Menu configurado com sucesso'
            ]);

        } catch (TelegramResponseException $e) {
            return response()->json([
                'message' => 'Token inválido ou erro na API do Telegram',
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao configurar o menu',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

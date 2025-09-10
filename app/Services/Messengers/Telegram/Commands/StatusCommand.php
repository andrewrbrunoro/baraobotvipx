<?php declare(strict_types=1);

namespace App\Services\Messengers\Telegram\Commands;

use App\Enums\PaymentStatusEnum;
use App\Models\ChatMember;
use App\Models\Gift;
use App\Models\InviteLink;
use App\Models\Order;
use App\Repositories\BotCommandRepository;
use App\Repositories\ChatMemberRepository;
use App\Repositories\ChatRepository;
use App\Repositories\MemberRepository;
use App\Services\Messengers\Telegram\Commands\Traits\AuthCommand;
use App\Services\Messengers\Telegram\Commands\Traits\HelperCommand;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use App\Services\Messengers\Telegram\Support\CallbackData;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;
use ZipStream\Exception;
use App\Services\Messengers\Telegram\ChatTelegramManager;
use App\Repositories\BotChatRepository;

class StatusCommand extends Command
{
    use AuthCommand,
        HelperCommand;

    protected string $name = 'status';

    protected string $description = 'Verifique a situação da sua assinatura';

    public function handle(): void
    {
        $member = $this->getMember();

        $result = ChatMemberRepository::make()
            ->subscribes($member->id);

        if (!$result->count()) {

            $order = Order::where('member_id', $member->id)
                ->orderByDesc('created_at')
                ->first();

            if (!$order) {
                $this->replyWithMessage([
                    'text' => 'Você ainda não tem uma assinatura, não perca tempo, faça parte do melhor grupo porno do telegram 🔞!',
                    'parse_mode' => 'MARKDOWN',
                ]);
                return;
            }

            if ($order->type === 'GIFT') {
                $gift = Gift::where('member_id', $member->id)
                    ->first();


                if (!$gift) {
                    $this->replyWithMessage([
                        'text' => <<<HTML
                        Nenhum cartão presente encontrado!
                        🎁 Compre mais cartão presente e mande para uma pessoa especial 😉
                        HTML,
                        'parse_mode' => 'MARKDOWN',
                    ]);
                } else if (!empty($gift->burn)) {
                    $this->replyWithMessage([
                        'text' => <<<HTML
                        💌 O seu presente já foi utilizado!

                        🎁 Compre mais cartão presente e mande para uma pessoa especial 😉
                        HTML,
                        'parse_mode' => 'MARKDOWN',
                    ]);
                } else {
                    $route = 'https://t.me/-1002914436230';
                    $giftPin = $gift->pin;

                    $this->replyWithMessage([
                        'text' => <<<HTML
                        Você está com um cartão presente ativo! 🌶️
                        💌 Siga as instruções e entregue o presente de um jeito especial:

                        1️⃣ Envie este link exclusivo para a pessoa que vai receber o presente: `$route`
                        2️⃣ Diga para ela usar o comando: `/gift $giftPin`

                        🎁 Pronto! O clima vai esquentar ainda mais. Aproveite! 💃🕺
                        HTML,
                        'parse_mode' => 'MARKDOWN',
                    ]);
                }

                return;
            } else {
                if ($order->status === PaymentStatusEnum::WAITING) {
                    $this->replyWithMessage([
                        'text' => <<<HTML
                            🚨 Você tem um pagamento pendente! 🚨
                            Finalize para poder ter acesso ao melhor grupo porno do telegram 🔞

                            Não perca tempo, venha aproveitar o conteúdo das melhores:

                            🥵 #XVIDEOSRED
                            🥵 #THAISSAFIT
                            🥵 #ANNYALVES
                            🥵 #CIBELLYFERREIRA
                            🥵 #ALINEFARIA
                            🥵 #ANDRESSAURACH
                            🥵 #MCPIPOKINHA

                            são mais de 7k de vídeos totalmente liberados!!!
                            HTML,
                        'parse_mode' => 'MARKDOWN'
                    ]);

                    return;
                }
            }
        }

        foreach ($result as $item) {
            $this->response($item);
        }
    }

    private function response(ChatMember $result)
    {
        $today = now();
        $date = $result->expired_at->format('d/m/Y');
        $time = $result->expired_at->format('H:i');

        if ($today->diffInSeconds($result->expired_at) < 0) {
            $this->replyWithMessage([
                'text' => <<<HTML
                ⏳ Pausa no prazer? Sua assinatura expirou dia $date às $time.
                Não deixe a diversão acabar! 💋
                💡 Digite /planos ou clique no botão abaixo e descubra como voltar a aproveitar tudo o que você merece.
                HTML,
                'parse_mode' => 'MARKDOWN',
                'reply_markup' => Button::make([
                    'text' => '🔥 Ver planos do VIP 🔥',
                    'callback_data' => CallbackData::make('products')
                        ->get()
                ])
            ]);
            return;
        }

        try {

            $linkName = 'CHANNEL_' . $result->chat_id;

            $inviteLink = InviteLink::where('name', $linkName)
                ->first();

            if (!$inviteLink) {

                $result = BotTelegram::make($this->getBot()->token)
                    ->api()
                    ->createChatInviteLink([
                        'chat_id' => $result->chat->code,
                        'user_id' => $result->member->code,
                        'creates_join_request' => true,
                    ]);

                $link = $result->invite_link;

                InviteLink::create([
                    'invite_link' => $link,
                    'member_id' => null,
                    'name' => $linkName,
                    'expire_date' => $result->expire_date,
                    'member_limit' => $result->member_limit,
                    'pending_join_request_count' => $result->pending_join_request_count,
                    'subscription_period' => $result->subscription_period ?? null,
                    'subscription_price' => $result->subscription_price ?? null,
                ]);
            } else {
                $link = $inviteLink->invite_link;
            }

            // $botAdmin = BotChatRepository::make()->getDefaultBot();
            // $botAdminApi = BotTelegram::make($botAdmin->token)
            //     ->api();

            // try {
            //     $botAdminApi->unbanChatMember([
            //         'chat_id' => $result->chat->code,
            //         'user_id' => $result->member->code,
            //     ]);
            // } catch (\Exception $e) {
            // }

            $supportEmail = env('SUPPORT_MAIL');

            $this->replyWithMessage([
                'text' => <<<HTML
                🔥 Você é VIP por aqui!
                Com sua assinatura em dia até $date às $time, o acesso ao melhor conteúdo segue garantido. 😘
                Relaxe e aproveite… a diversão não tem hora pra acabar!

                Caso esteja enfrentando problemas para acessar o grupo, envie um e-mail para $supportEmail
                HTML,
                'parse_mode' => 'HTML',
                'reply_markup' => Keyboard::make()
                    ->inline()
                    ->setResizeKeyboard(false)
                    ->setSelective(true)
                    ->row([
                        Button::make()
                            ->setText('Voltar para o grupo')
                            ->setUrl($link)
                    ])
            ]);
        } catch (Exception $e) {
            $this->replyWithMessage([
                'text' => <<<HTML
                🔥 Você é VIP por aqui!
                Com sua assinatura em dia até $date às $time, o acesso ao melhor conteúdo segue garantido. 😘
                Relaxe e aproveite… a diversão não tem hora pra acabar!
                HTML,
                'parse_mode' => 'HTML',
            ]);
        }
    }

    public function getOnlyOwner(): bool
    {
        return false;
    }

}

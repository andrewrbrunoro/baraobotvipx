<?php declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Models\Remarketing;
use App\Models\TelegramVideo;
use App\Repositories\MemberLogRepository;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderRemarketingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $memberId,
        private readonly string $memberCode,
        private readonly string $memberName,
        private readonly string $botToken,
        private readonly string $campaign
    ) {}

    public function handle(): void
    {
        $exists = Remarketing::where(function($q) {
            $q->where('member_id', $this->memberId)
                ->where('campaign', $this->campaign);
        })->exists();

        if ($exists)
            return;

        $result = TelegramVideo::where('name', 'video_acabou')
            ->first();

        BotTelegram::make($this->botToken)
            ->api()
            ->sendVideo([
                'chat_id' => $this->memberCode,
                'parse_mode' => 'HTML',
                'video' => $result->telegram_id,
                'caption' => <<<HTML
                Ficou quente, mas parou na metade? 🔥

                Termine agora sua assinatura e garanta momentos de pura satisfação.
                Temos muito mais esperando por você... Vem terminar o que começou! 💦😉💋

                Clique em /planos ou acesse o menu e venha se deliciar!
                HTML
            ]);

        MemberLogRepository::make()
            ->save(
                $this->memberCode,
                'remarketing',
                $this->memberName,
                'Membro recebeu remarketing',
            );

        Remarketing::firstOrCreate([
            'member_id' => $this->memberId,
            'campaign' => $this->campaign,
        ]);
    }
}

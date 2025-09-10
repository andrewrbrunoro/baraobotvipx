<?php declare(strict_types=1);

namespace App\Jobs\Notifications;

use App\Models\Bot;
use App\Models\Order;
use App\Services\Messengers\Telegram\Support\AppNotificationSupport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NewOrderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Bot   $bot,
        public Order $order
    )
    {}

    public function handle(): void
    {
        AppNotificationSupport::make($this->bot)
            ->newOrder($this->order);
    }
}

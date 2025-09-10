<?php declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CampaignEventEnum;
use App\Models\Campaign;
use App\Models\Member;
use App\Models\Remarketing;
use Illuminate\Console\Command;

class ExecuteCampaignsCommand extends Command
{
    protected $signature = 'campaigns:execute';

    protected $description = 'Executa as campanhas ativas baseadas em eventos e condições';

    public function handle(): int
    {
        $this->info('Iniciando execução das campanhas...');

        // Busca todas as campanhas ativas
        $campaigns = Campaign::where('is_active', true)->get();

        foreach ($campaigns as $campaign) {
            $this->info("Processando campanha: {$campaign->name}");

            // Processa os membros em lotes de 100
            $this->getMembersForCampaign($campaign)->chunk(100, function ($members) use ($campaign) {
                foreach ($members as $member) {
                    // Verifica se já existe um remarketing para este membro e campanha
                    $existingRemarketing = Remarketing::where('member_id', $member->id)
                        ->where('campaign_id', $campaign->id)
                        ->first();

                    if ($existingRemarketing) {
                        $this->info("Membro {$member->id} já recebeu esta campanha. Pulando...");
                        continue;
                    }

                    // Busca o bot_id baseado no tipo de campanha
                    $botId = null;

                    if (in_array($campaign->event->value, [
                        CampaignEventEnum::ORDER_SUCCESS->value,
                        CampaignEventEnum::ORDER_FAIL->value
                    ])) {
                        // Se for campanha de pedido, pega o bot_id do último pedido
                        $botId = $member->orders()
                            ->latest()
                            ->value('bot_id');
                    } else {
                        // Se não for campanha de pedido, pega o bot_id do último contato
                        $botId = $member->lastBotMember->bot_id;
                    }

                    if (!$botId) {
                        $this->warn("Membro {$member->id} não possui bot associado. Pulando...");
                        continue;
                    }

                    // Cria o remarketing
                    Remarketing::create([
                        'member_id' => $member->id,
                        'bot_id' => $botId,
                        'campaign_id' => $campaign->id,
                        'status' => 'PENDING'
                    ]);

                    $this->info("Campanha agendada para o membro {$member->id} com bot {$botId}");
                }
            });
        }

        $this->info('Execução das campanhas finalizada!');
        return self::SUCCESS;
    }

    private function getMembersForCampaign(Campaign $campaign): \Illuminate\Database\Eloquent\Builder
    {
        $query = Member::query();
        $timerSeconds = $campaign->timer_seconds;

        $this->info("Buscando membros para campanha {$campaign->name}");
        $this->info("Timer em segundos: {$timerSeconds}");

        // Aplica as condições baseadas no evento
        switch ($campaign->event->value) {
            case CampaignEventEnum::ORDER_SUCCESS->value:
                $query->whereHas('orders', function ($query) use ($campaign) {
                    $query->where('status', 'SUCCESS')
                        ->whereRaw("TIMESTAMPDIFF(SECOND, created_at, NOW()) >= ?", [$campaign->timer_seconds])
                        ->whereRaw("TIMESTAMPDIFF(SECOND, created_at, NOW()) <= ?", [$campaign->timer_seconds + 60]);
                    $this->applyConditions($query, $campaign->conditions);
                });
                break;

            case CampaignEventEnum::ORDER_FAIL->value:
                $query->whereHas('orders', function ($query) use ($campaign) {
                    $query->whereIn('status', ['FAILURE', 'WAITING'])
                        ->whereRaw("TIMESTAMPDIFF(SECOND, created_at, NOW()) >= ?", [$campaign->timer_seconds])
                        ->whereRaw("TIMESTAMPDIFF(SECOND, created_at, NOW()) <= ?", [$campaign->timer_seconds + 60]);
                    $this->applyConditions($query, $campaign->conditions);
                });
                break;

            case CampaignEventEnum::MEMBERSHIP_EXPIRED->value:
                $query->whereHas('chatMember', function ($query) use ($campaign) {
                    $query->whereRaw("TIMESTAMPDIFF(SECOND, expires_at, NOW()) >= ?", [$campaign->timer_seconds])
                        ->whereRaw("TIMESTAMPDIFF(SECOND, expires_at, NOW()) <= ?", [$campaign->timer_seconds + 60]);
                });
                break;

            case CampaignEventEnum::MEMBERSHIP_EXPIRING->value:
                $query->whereHas('chatMember', function ($query) use ($campaign) {
                    $query->where('expires_at', '>', now())
                        ->where('expires_at', '<=', now()->addDays(7))
                        ->whereRaw("TIMESTAMPDIFF(SECOND, created_at, NOW()) >= ?", [$campaign->timer_seconds])
                        ->whereRaw("TIMESTAMPDIFF(SECOND, created_at, NOW()) <= ?", [$campaign->timer_seconds + 60]);
                    $this->applyConditions($query, $campaign->conditions);
                });
                break;

            case CampaignEventEnum::MEMBERSHIP_CREATED->value:
                $query->whereHas('chatMember', function ($query) use ($campaign) {
                    $query->whereRaw("TIMESTAMPDIFF(SECOND, created_at, NOW()) >= ?", [$campaign->timer_seconds])
                        ->whereRaw("TIMESTAMPDIFF(SECOND, created_at, NOW()) <= ?", [$campaign->timer_seconds + 60]);
                });
                break;

            case CampaignEventEnum::MEMBERSHIP_RENEWED->value:
                $query->whereHas('chatMember', function ($query) use ($campaign) {
                    $query->whereRaw("TIMESTAMPDIFF(SECOND, expired_at, NOW()) >= ?", [$campaign->timer_seconds])
                        ->whereRaw("TIMESTAMPDIFF(SECOND, expired_at, NOW()) <= ?", [$campaign->timer_seconds + 60]);
                });
                break;
        }

        // Adiciona distinct para evitar duplicatas
        $query->distinct();

        // Debug da query
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        $this->info("SQL Query: " . $sql);
        $this->info("Bindings: " . json_encode($bindings));

        return $query;
    }

    private function applyConditions($query, array $conditions): void
    {
        foreach ($conditions as $key => $value) {
            switch ($key) {
                case 'status':
                    if (is_array($value)) {
                        $query->whereIn('status', $value);
                    } else {
                        $query->where('status', $value);
                    }
                    break;

                case 'days_remaining':
                    if (is_array($value)) {
                        $query->where(function ($query) use ($value) {
                            foreach ($value as $days) {
                                $query->orWhere('expires_at', '=', now()->addDays($days));
                            }
                        });
                    } else {
                        $query->where('expires_at', '=', now()->addDays($value));
                    }
                    break;

                case 'min_value':
                    $query->where('total', '>=', $value);
                    break;

                case 'plan_id':
                    $query->where('plan_id', $value);
                    break;

                case 'hour':
                    $query->whereHour('created_at', $value);
                    break;
            }
        }
    }
}

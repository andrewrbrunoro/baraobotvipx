<?php declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Remarketing;
use Illuminate\Console\Command;
use App\Services\Messengers\Telegram\Support\BotTelegram;;

class ExecuteRemarketingsCommand extends Command
{
    protected $signature = 'remarketings:execute';

    protected $description = 'Executa os remarketings pendentes que estão agendados para o momento atual';

    public function handle(): int
    {
        $this->info('Iniciando execução dos remarketings...');

        // Busca todos os remarketings pendentes que estão agendados para agora
        $remarketings = Remarketing::where('status', 'PENDING')
            ->get();

        $this->info("Encontrados {$remarketings->count()} remarketings para executar");

        foreach ($remarketings as $remarketing) {
            $this->info("Processando remarketing ID: {$remarketing->id}");

            try {
                // Aqui você pode adicionar a lógica específica para cada tipo de campanha
                // Por exemplo, enviar mensagem, notificação, etc.

                $campaign = Campaign::where('code', $remarketing->campaign)->first();
                if (!$campaign) {
                    $this->error("Campanha não encontrada para o remarketing ID: {$remarketing->id}");
                    continue;
                }

                BotTelegram::make($remarketing->bot->token)
                    ->api()
                    ->sendMessage([
                        'chat_id' => $remarketing->member->code,
                        'parse_mode' => 'HTML',
                        'text' => $campaign->description
                    ]);


                // Atualiza o status para EXECUTED
                $remarketing->update([
                    'status' => 'EXECUTED',
                    'executed_at' => now()
                ]);

                $this->info("Remarketing ID: {$remarketing->id} executado com sucesso");
            } catch (\Exception $e) {
                $this->error("Erro ao executar remarketing ID: {$remarketing->id}");
                $this->error($e->getMessage());

                // Atualiza o status para FAILED
                $remarketing->update([
                    'status' => 'FAILED',
                    'error_message' => $e->getMessage()
                ]);
            }
        }

        $this->info('Execução dos remarketings finalizada!');
        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\ListenChatMemberJob;
use App\Repositories\ChatMemberRepository;
use Illuminate\Console\Command;

class ListenChatMemberCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat-member:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica se o tempo usuário não expirou';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting ListenChatMemberCommand execution...');
        $this->info('Current Date: ' . now()->format('Y-m-d H:i:s'));
        $startTime = now();

        try {
            $this->info('Fetching expired chat members...');
            $chunks = ChatMemberRepository::make()->listenExpiredDate();
            
            $totalChunks = count($chunks);
            $this->info("Found {$totalChunks} chunks of expired chat members");

            if ($totalChunks === 0) {
                $this->warn('No expired chat members found');
                return;
            }

            $processedChunks = 0;
            foreach ($chunks as $index => $chunk) {
                $this->info("Processing chunk " . ($index + 1) . "/{$totalChunks} with " . count($chunk) . " members");
                
                // Debug: Log chunk details
                $this->info("Chunk details: " . json_encode($chunk->toArray(), JSON_PRETTY_PRINT));
                
                try {
                    dispatch(new ListenChatMemberJob($chunk));
                    $processedChunks++;
                    $this->info("✓ Chunk " . ($index + 1) . " dispatched successfully");
                } catch (\Exception $e) {
                    $this->error("✗ Failed to dispatch chunk " . ($index + 1) . ": " . $e->getMessage());
                    $this->error("Error file: " . $e->getFile() . " Line: " . $e->getLine());
                    
                    \Log::error('ListenChatMemberCommand: Failed to dispatch chunk', [
                        'chunk_index' => $index + 1,
                        'chunk_size' => count($chunk),
                        'chunk_data' => $chunk->toArray(),
                        'error' => $e->getMessage(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $executionTime = $startTime->diffInSeconds(now());
            $this->info("Command completed successfully!");
            $this->info("Processed {$processedChunks}/{$totalChunks} chunks in {$executionTime} seconds");

        } catch (\Exception $e) {
            $this->error("Command failed: " . $e->getMessage());
            \Log::error('ListenChatMemberCommand: Command execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

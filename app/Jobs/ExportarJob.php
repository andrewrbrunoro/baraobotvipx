<?php

namespace App\Jobs;

use App\Models\Temp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExportarJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $url, public string $filename = 'tudo_puta')
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJkYXRhVXNlciI6eyJ1c2VySWQiOjgwMywiZW1haWwiOiJqZW1hcnRvcmlAZ21haWwuY29tIiwidGVsbCI6IjUxOTgzMjg2NDQyIiwibmFtZSI6IkrDqXNzaWNhIiwiaGFzaFBhc3MiOiJ3NlVETHoyN1kxSytGVXQwQWduQVp1RDFhZ01NcXFHaDFGSjF4UStiNVc4PSIsInVzZXJMZXZlbCI6IkNMSUVOVCIsImNyZWF0ZWRBdCI6IjIwMjQtMDItMTRUMjI6NTY6MTYuMzAxWiIsInRlcm1zT2ZBbm5vdW5jZW1lbnQiOmZhbHNlLCJhdXRoVHlwZSI6IlBBU1NXT1JEIiwiaXNCYW5uZWQiOmZhbHNlLCJvYnMiOm51bGwsInBlbmRpbmdTbXNWYWxpZGF0aW9uIjpmYWxzZSwiaXNQYXNzd29yZERlZmluZWQiOmZhbHNlLCJvYnMyIjpudWxsLCJqb2luZWRQYXJ0bmVySWQiOm51bGwsImZlZURlZmluaXRpb25DdXBvbUlkIjpudWxsfSwiaWF0IjoxNzM0MTI2NjI0LCJleHAiOjE3MzY4MDUwMjR9.6E5eYqgK0ftoIcvSyROelt34559gXKbg1aU0-HDg1fk';

        $request = \Illuminate\Support\Facades\Http::withToken($token)
            ->timeout(5000)
            ->get(
                $this->url
            );

        if ($request->successful()) {
            $data = $request->json('data');
            if (!isset($data['pages'])) {
                info('=> Não achou pages: ' . $this->url);
                return;
            }

            $pages = $data['pages'];

            if (!isset($pages[0]['items'])) {
                info('=> Não achou items: ' . $this->url);
                return;
            }

            info($this->url);
            info(count($pages));

            $this->writeOnCsvFile($pages);
        } else {
            info(sprintf('=> erro ao ler a url: %s', $this->url));
            info(print_r($request->json(), true));
        }
    }

    public function writeOnCsvFile(array $pages): void
    {
//        $fileName = public_path($this->filename . '.csv');

//        $file = fopen($fileName, 'a');

        foreach ($pages as $line) {
            foreach ($line['items'] as $fields) {
                $payload = [
                    'member_id' => $fields['id'],
                    'name' => $fields['name'] ?? 'Sem Nome',
                    'expired_at' => $fields['expiresAt'] ?? '',
                    'phone' => $fields['phone'] ?? '',
                ];

                $uuid = md5(json_encode($payload));

                Temp::create([
                    'uuid' => $uuid,
                    ...$payload
                ]);

//                fputcsv($file, [
//                    'id' => $fields['id'],
//                    'name' => $fields['name'] ?? 'Sem Nome',
//                    'expired_at' => $fields['expiresAt'] ?? '',
//                    'phone' => $fields['phone'] ?? '',
//                ]);
            }
        }

//        fclose($file);
    }
}

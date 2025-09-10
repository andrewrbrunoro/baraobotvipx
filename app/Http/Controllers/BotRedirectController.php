<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bot;

class BotRedirectController extends Controller
{

    public function __invoke()
    {
        $bot = Bot::query();

        if (request()->get('id')) {
            $bot->where('id', request()->get('id'));
        } else {
            $bot->where('principal', 1);
        }

        $bot = $bot->first();

        if (!$bot) {
            info('BotRedirectController: Bot not found', [
                'params' => request()->all(),
            ]);
            abort(404);
        }

        return redirect()->to('https://t.me/' . $bot->username);
    }

    // public function __invoke()
    // {
    //     $bot = Bot::query();

    //     if (request()->get('id')) {
    //         $bot->where('id', request()->get('id'));
    //     } else {
    //         $bot->where('status', 'active')->inRandomOrder();
    //     }

    //     $bot = $bot->first();

    //     if (!$bot) {
    //         info('BotRedirectController: Bot not found', [
    //             'params' => request()->all(),
    //         ]);
    //         abort(404);
    //     }

    //     return redirect()->to('https://t.me/' . $bot->username);
    // }
}

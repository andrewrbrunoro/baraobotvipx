<?php declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use Lunaweb\RecaptchaV3\Facades\RecaptchaV3;

class LandingPageController extends Controller
{

    public function __invoke(): View
    {
        $marketing = [
            'Cobranças via Pix e cartão',
            'Membros entram e saem automaticamente',
            'Renovação feita sem erro',
            'Suporte top e rápido',
            'Crie quantos bots quiser, sem pagar a mais',
        ];
        session()->forget('success');

        return view('lp.index', compact('marketing'));
    }

    public function contact(Request $request)
    {
        $result = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:contacts,email',
            'g-recaptcha-response' => 'required|recaptchav3:register,0.5',
        ], [
            'g-recaptcha-response.recaptchav3' => 'Por favor, complete a verificação de segurança.',
        ]);

        $result = Contact::create($request->only('name', 'email'));
        if ($result) {

            $name = $request->name;
            $email = $request->email;

            $botTelegram = BotTelegram::make(config('services.telegram.client_secret'));
            $botTelegram->api()->sendMessage([
                'chat_id' => config('services.telegram.notify_group'),
                'text' => "Cadastrou o contato na Landing Page\n\nNome: $name\nEmail: $email",
                'parse_mode' => 'Markdown',
            ]);

            session()->put('success', 'Obrigado pelo seu interesse! Em breve entraremos em contato com você.');

            return redirect()->route('lp.index');
        }

        return redirect()
            ->route('lp.index')
            ->with('error', 'Ocorreu um erro ao enviar o formulário.');
    }
}

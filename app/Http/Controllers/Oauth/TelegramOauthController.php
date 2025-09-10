<?php declare(strict_types=1);

namespace App\Http\Controllers\Oauth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\UserRepository;
use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TelegramOauthController extends Controller
{
    protected UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = UserRepository::make();
    }

    public function __invoke(Request $request): RedirectResponse
    {
        try {

            if (!$this->checkHash($request))
                return redirect('/admin/login?telegram=unauthorized');

            $telegramOwnerCode = $request->get('id');

            $firstName = $request->get('first_name', '');
            $lastName = $request->get('last_name', '');
            $username = $request->get('username');
            $photoUrl = $request->get('photo_url', '');
            $name = sprintf('%s %s', $firstName, $lastName);

            $notification = Notification::make();

            $userAlreadyHaveTelegramConnected = $this->userRepository->alreadyHaveTelegramAccount(
                $telegramOwnerCode,
            );

            if ($userAlreadyHaveTelegramConnected) {

                Auth::login($userAlreadyHaveTelegramConnected);

                $notification
                    ->success()
                    ->body(__('Você está conectado com sucesso'));

                $userAlreadyHaveTelegramConnected
                    ->update([
                        'name' => $name ?? $userAlreadyHaveTelegramConnected->name,
                        'username' => $username ?? $userAlreadyHaveTelegramConnected->username,
                        'photo_url' => $photoUrl ?? $userAlreadyHaveTelegramConnected->photo_url,
                        'telegram_owner_code' => $telegramOwnerCode ?? $userAlreadyHaveTelegramConnected->telegram_owner_code,
                    ]);

                return redirect('/admin');
            }

            $user = User::create([
                'name' => trim($name),
                'username' => $username,
                'photo_url' => $photoUrl,
                'email' => sprintf('%s@telegram.org', $telegramOwnerCode),
                'telegram_owner_code' => $telegramOwnerCode,
                'password' => Str::uuid()->toString(),
                'telegram_hash' => $request->get('hash'),
            ]);

            SocialiteUser::firstOrCreate([
                'user_id' => $user->id,
                'provider' => 'telegram',
                'provider_id' => $telegramOwnerCode,
            ]);

            $notification
                ->success()
                ->body(__('Olá :name, que bom que você se juntou ao :project', [
                    'name' => $name,
                    'project' => env('APP_NAME')
                ]));

            Auth::login($user);

            return redirect('/admin');
        } catch (Exception $e) {
            return redirect('/admin/login?code=501');
        }
    }

    private function checkHash(Request $request): bool
    {
        $authData = $request->except(['hash']);
        $checkHash = $request->get('hash');

        $dataCheck = [];
        foreach ($authData as $key => $value) {
            $dataCheck[] = $key . '=' . $value;
        }

        sort($dataCheck);

        $dataCheckString = implode("\n", $dataCheck);

        $secretKey = hash('sha256', env('APP_BOT_TELEGRAM_TOKEN'), true);

        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (strcmp($hash, $checkHash) !== 0)
            return false;

        if ((time() - $authData['auth_date']) > 86400)
            return false;

        return true;
    }
}

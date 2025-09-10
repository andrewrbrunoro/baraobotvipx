<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Resources\PaymentPlatformResource\Pages\ListPaymentPlatforms;
use App\Services\Payments\MercadoPago\OauthMercadoPago;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;

class RedirectPlatformController extends Controller
{

    public function mercadoPago(): RedirectResponse
    {
        $result = OauthMercadoPago::make()
            ->setup(
                request('code'),
                request('state')
            );

        $notification = Notification::make();

        if (!$result) {
            $notification
                ->danger()
                ->body(__('Não foi possível sincronizar sua conta.'));
        } else {
            $notification
                ->success()
                ->body(__('Conta sincronizada com sucesso!'));
        }

        $notification->send();

        return redirect(ListPaymentPlatforms::getUrl());
    }

}

<?php declare(strict_types=1);

namespace App\Filament\Resources\PaymentPlatformResource\Actions;

use App\Enums\PaymentPlatformEnum;
use App\Filament\Resources\PaymentPlatformResource\Pages\ListPaymentPlatforms;
use App\Models\UserPaymentIntegration;
use App\Services\Payments\MercadoPago\OauthMercadoPago;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;

class MercadoPagoAction extends Action
{
    use CanCustomizeProcess;

    protected function setUp(): void
    {
        parent::setUp();

        $user = auth()->user();
        $platforms = $user->paymentPlatforms;

        if ($platforms->where('platform', PaymentPlatformEnum::MERCADO_PAGO)->count() > 0)
            $this->label(__('Desconectar'))
                ->button()
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (Action $action): void {
                    $this->process(function () use ($action) {
                        $result = UserPaymentIntegration::where('user_id', auth()->user()->id)
                            ->where('platform', PaymentPlatformEnum::MERCADO_PAGO)
                            ->delete();

                        $notification = Notification::make();

                        if (!$result) {
                            $notification
                                ->danger()
                                ->body(__('Não foi possível desconectar, tente novamente mais tarde, caso o erro persita entre em contato conosco!'));
                        } else {
                            $notification
                                ->success()
                                ->body(
                                    __('Você foi desconectado pela :project.', [
                                        'project' => env('APP_NAME')
                                    ])
                                );
                        }

                        $notification->send();

                        redirect(ListPaymentPlatforms::getUrl());
                    });
                });
        else {
            $this->label(__('Conectar'))
                ->button()
                ->color('primary')
                ->modal()
                ->modalHeading(__('Integração com MercadoPago'))
                ->modalContent(
                    new HtmlString(<<<HTML
                    Você está sendo redirecionado para o Mercado Pago para completar uma etapa necessária da integração. <br>
                    Após a conclusão, você será automaticamente retornado ao nosso site. <br><br>
                    Clique em continuar para proesseguir. <br>
                    HTML
                    )
                )
                ->modalSubmitActionLabel(__('Continuar'))
                ->action(function (): void {
                    $this->process(function () {
                        $hash = encrypt(auth()->user()->id);

                        $url = OauthMercadoPago::make()
                            ->oauth2Url($hash);

                        redirect($url);
                    });
                });
        }
    }

    public function getName(): ?string
    {
        return 'mercado_pago';
    }

}

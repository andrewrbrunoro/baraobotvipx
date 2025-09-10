<?php declare(strict_types=1);

namespace App\Filament\Resources\BotCommandResource\Pages;

use App\Filament\Resources\BotCommandResource;
use App\Models\BotCommand;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;

class EditBotCommand extends EditRecord
{
    protected static string $resource = BotCommandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('return')
                ->label(__('Voltar a versão padrão'))
                ->color('info')
                ->action(
                    function (BotCommand $record, Actions\Action $action) {
                        $notification = Notification::make();

                        $result = $record->update([
                            'description' => null
                        ]);

                        if (!$result) {
                            $notification
                                ->title(__('Ops!'))
                                ->body(__('Erro ao resetar o comando, tente novamente, caso o erro persita entre em contato conosco!'))
                                ->danger()
                                ->send();
                        } else {
                            $notification
                                ->title(__('Pronto!'))
                                ->body(__('Comando resetado com sucesso!'))
                                ->success()
                                ->send();
                        }
                    }
                )
//            Actions\Action::make('delete')
//                ->color('danger')
//                ->label(__('Excluir'))
//                ->action(
//                    function (BotCommand $record, Actions\Action $action) {
//                        $notification = Notification::make();
//
//                        $result = $record->delete();
//
//
//                        if (!$result) {
//                            $notification
//                                ->title('Ops!')
//                                ->body(__('Não foi possível deletar o registro.'))
//                                ->danger()
//                                ->send();
//                        } else {
//                            $notification
//                                ->title('Tudo certo!')
//                                ->body(__('Registro deletado com sucesso'))
//                                ->success()
//                                ->send();
//
//                            $action->redirect(
//                                BotCommandResource::getUrl('index', ['bot_id' => $record->bot_id])
//                            );
//                        }
//                    }
//                )
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            new HtmlString(sprintf('<a href="%s">Comandos do Bot</a>', BotCommandResource::getUrl('index', ['bot_id' => request('bot_id')]))),
            __('Editar')
        ];
    }


    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
        ];
    }
}

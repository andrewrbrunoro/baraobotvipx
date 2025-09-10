<?php declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CampaignEventEnum;
use App\Filament\Resources\CampaignResource\Pages;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;
use Filament\Tables\Actions\Action;
use App\Models\Member;
use App\Models\Bot;
use App\Services\Messengers\Telegram\Support\BotTelegram;
use Illuminate\Support\Facades\Notification;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $label = 'Campanha';

    protected static ?string $pluralLabel = 'Campanhas';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Exemplos de Campanhas')
                    ->description('Aqui estão alguns exemplos de como criar campanhas efetivas')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('exemplos')
                            ->content(new HtmlString(<<<HTML
                            <div class="space-y-6">
                                <div class="space-y-4">
                                    <h3 class="font-bold text-lg">Exemplos de Eventos</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold mb-2">Pedido Finalizado com Sucesso</h4>
                                            <p class="text-sm">Envie um agradecimento quando o cliente finalizar a compra</p>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold mb-2">Pedido Falhou</h4>
                                            <p class="text-sm">Envie um e-mail de recuperação quando o cliente abandonar o carrinho</p>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold mb-2">Assinatura Expirada</h4>
                                            <p class="text-sm">Envie uma oferta especial para reativar a assinatura</p>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold mb-2">Assinatura Prestes a Expirar</h4>
                                            <p class="text-sm">Envie um lembrete para renovar a assinatura</p>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold mb-2">Nova Assinatura</h4>
                                            <p class="text-sm">Envie um e-mail de boas-vindas para novos assinantes</p>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold mb-2">Assinatura Renovada</h4>
                                            <p class="text-sm">Envie um agradecimento pela renovação</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <h3 class="font-bold text-lg">Exemplos de Condições</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold mb-2">Status do Pedido</h4>
                                            <p class="text-sm"><code>{"status": "WAITING"}</code></p>
                                            <p class="text-sm text-gray-600">Para pedidos em espera</p>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold mb-2">Dias Restantes</h4>
                                            <p class="text-sm"><code>{"days_remaining": 1}</code></p>
                                            <p class="text-sm text-gray-600">Para assinaturas com 1 dia para expirar</p>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold mb-2">Valor Mínimo</h4>
                                            <p class="text-sm"><code>{"min_value": 100}</code></p>
                                            <p class="text-sm text-gray-600">Para pedidos acima de R$ 100</p>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold mb-2">Plano Específico</h4>
                                            <p class="text-sm"><code>{"plan_id": 1}</code></p>
                                            <p class="text-sm text-gray-600">Para assinaturas do plano 1</p>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold mb-2">Horário Específico</h4>
                                            <p class="text-sm"><code>{"hour": 23}</code></p>
                                            <p class="text-sm text-gray-600">Para pedidos às 23h</p>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold mb-2">Múltiplos Valores</h4>
                                            <p class="text-sm"><code>{"status": ["WAITING", "FAILURE"]}</code></p>
                                            <p class="text-sm text-gray-600">Para pedidos em espera ou falhos</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <h3 class="font-bold text-lg">Exemplos de Campanhas Completas</h3>
                                    <div class="space-y-4">
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold text-lg mb-2">Campanha para Pedidos que falharam</h4>
                                            <div class="space-y-1 text-sm">
                                                <p><strong>Evento:</strong> Pedido Falhou</p>
                                                <p><strong>Condições:</strong> <code>{"min_value": 100, "hour": 23}</code></p>
                                                <p><strong>Tempo:</strong> 0 segundos (imediato)</p>
                                                <p><strong>Resultado:</strong> Envia e-mail de recuperação imediatamente para pedidos acima de R$ 100 que falharam às 23h</p>
                                            </div>
                                        </div>

                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold text-lg mb-2">Campanha para Pedidos que falharam (com delay)</h4>
                                            <div class="space-y-1 text-sm">
                                                <p><strong>Evento:</strong> Pedido Falhou</p>
                                                <p><strong>Condições:</strong> <code>{"min_value": 100}</code></p>
                                                <p><strong>Tempo:</strong> 900 segundos (15 minutos)</p>
                                                <p><strong>Resultado:</strong> Envia e-mail de recuperação 15 minutos após o pedido falhar, apenas para pedidos acima de R$ 100</p>
                                            </div>
                                        </div>

                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <h4 class="font-bold text-lg mb-2">Campanha para Assinaturas prestes a expirar</h4>
                                            <div class="space-y-1 text-sm">
                                                <p><strong>Evento:</strong> Assinatura Prestes a Expirar</p>
                                                <p><strong>Condições:</strong> <code>{"days_remaining": [1, 3, 7]}</code></p>
                                                <p><strong>Tempo:</strong> 0 segundos (imediato)</p>
                                                <p><strong>Resultado:</strong> Envia lembretes imediatamente quando faltar 7, 3 e 1 dia para a expiração</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            HTML)),
                    ]),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('Ativo'))
                    ->helperText('Ative ou desative esta campanha. Campanhas inativas não serão executadas.')
                    ->required()
                    ->default(true),
                Forms\Components\TextInput::make('code')
                    ->label(__('Código'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->label(__('Nome'))
                    ->required()
                    ->maxLength(255),
                MarkdownEditor::make('description')
                    ->label(__('Descrição'))
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',          // <b>bold</b>, <strong>bold</strong>, **bold**
                        'italic',        // <i>italic</i>, <em>italic</em>, *italic*
                        'codeBlock',     // <code>code</code>, `code`
                        'strike',        // <s>strike</s>, <strike>strike</strike>, <del>strike</del>, ~~strike~~
                        'underline',     // <u>underline</u>
                    ]),
                Forms\Components\Select::make('event')
                    ->label(__('Evento'))
                    ->options([
                        CampaignEventEnum::ORDER_SUCCESS->value => 'Pedido Finalizado com Sucesso',
                        CampaignEventEnum::ORDER_FAIL->value => 'Pedido Falhou ou Não Finalizado',
                        CampaignEventEnum::MEMBERSHIP_EXPIRED->value => 'Assinatura Expirada',
                        CampaignEventEnum::MEMBERSHIP_EXPIRING->value => 'Assinatura Prestes a Expirar',
                        CampaignEventEnum::MEMBERSHIP_CREATED->value => 'Nova Assinatura Criada',
                        CampaignEventEnum::MEMBERSHIP_RENEWED->value => 'Assinatura Renovada',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('timer_seconds')
                    ->label(__('Tempo (segundos)'))
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Tempo em segundos para executar a campanha após o evento. Use 0 para execução imediata.'),
                Forms\Components\KeyValue::make('conditions')
                    ->label(__('Condições'))
                    ->nullable()
                    ->columnSpanFull()
                    ->keyLabel('Tipo de Condição')
                    ->valueLabel('Valor')
                    ->keyPlaceholder('Selecione o tipo')
                    ->valuePlaceholder('Digite o valor')
                    ->addButtonLabel('Adicionar Condição')
                    ->reorderable(false)
                    ->default([])
                    ->validationAttribute('condições')
                    ->helperText(new HtmlString(<<<HTML
                        <p>Tipos de condições disponíveis:</p>
                        <ul class="list-disc list-inside">
                            <li><code>status</code> - Status do Pedido (WAITING, FAILURE, SUCCESS)</li>
                            <li><code>days_remaining</code> - Dias Restantes para expiração</li>
                            <li><code>min_value</code> - Valor Mínimo do pedido</li>
                            <li><code>plan_id</code> - ID do Plano específico</li>
                            <li><code>hour</code> - Horário específico (0-23)</li>
                        </ul>
                    HTML)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('Código'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Nome'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('event')
                    ->label(__('Evento'))
                    ->formatStateUsing(fn ($state) => match($state) {
                        CampaignEventEnum::ORDER_SUCCESS->value => 'Pedido Finalizado com Sucesso',
                        CampaignEventEnum::ORDER_FAIL->value => 'Pedido Falhou ou Não Finalizado',
                        CampaignEventEnum::MEMBERSHIP_EXPIRED->value => 'Assinatura Expirada',
                        CampaignEventEnum::MEMBERSHIP_EXPIRING->value => 'Assinatura Prestes a Expirar',
                        CampaignEventEnum::MEMBERSHIP_CREATED->value => 'Nova Assinatura Criada',
                        CampaignEventEnum::MEMBERSHIP_RENEWED->value => 'Assinatura Renovada',
                        default => $state,
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('timer_seconds')
                    ->label(__('Tempo'))
                    ->formatStateUsing(fn ($state) => $state === 0 ? 'Imediato' : gmdate('H:i:s', $state))
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Ativo'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Criado em'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Atualizado em'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label(__('Evento'))
                    ->options([
                        CampaignEventEnum::ORDER_SUCCESS->value => 'Pedido Finalizado com Sucesso',
                        CampaignEventEnum::ORDER_FAIL->value => 'Pedido Falhou ou Não Finalizado',
                        CampaignEventEnum::MEMBERSHIP_EXPIRED->value => 'Assinatura Expirada',
                        CampaignEventEnum::MEMBERSHIP_EXPIRING->value => 'Assinatura Prestes a Expirar',
                        CampaignEventEnum::MEMBERSHIP_CREATED->value => 'Nova Assinatura Criada',
                        CampaignEventEnum::MEMBERSHIP_RENEWED->value => 'Assinatura Renovada',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Ativo')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('test')
                    ->label('Testar Campanha')
                    ->icon('heroicon-o-play')
                    ->form([
                        Forms\Components\Select::make('member_id')
                            ->label('Membro')
                            ->options(Member::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('bot_id')
                            ->label('Bot')
                            ->options(Bot::pluck('first_name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Campaign $record, array $data): void {
                        // Cria o remarketing para teste
                        $remarketing = \App\Models\Remarketing::create([
                            'member_id' => $data['member_id'],
                            'campaign_id' => $record->id,
                            'bot_id' => $data['bot_id'],
                            'status' => 'PENDING',
                            'scheduled_at' => now()->addSeconds($record->timer_seconds),
                        ]);

                        // Envia a mensagem usando o BotTelegram
                        $bot = Bot::find($data['bot_id']);
                        $member = Member::find($data['member_id']);

                        $result = BotTelegram::make($bot->token)
                            ->api()
                            ->sendMessage([
                                'chat_id' => $member->code,
                                'parse_mode' => 'HTML',
                                'text' => $record->description
                            ]);

                        Notification::make()
                            ->title('Teste de Campanha enviada com sucesso')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Testar Campanha')
                    ->modalDescription('Esta ação irá enviar um teste desta campanha para o membro selecionado.')
                    ->modalSubmitActionLabel('Sim, testar')
                    ->modalCancelActionLabel('Não, cancelar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}

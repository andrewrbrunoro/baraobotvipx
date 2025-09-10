<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Leandrocfe\FilamentPtbrFormFields\Money;

class GridsRelationManager extends RelationManager
{
    protected static string $relationship = 'grids';

    protected static ?string $title = 'Variações';

    protected static ?string $label = 'Variação do Produto';

    protected static ?string $pluralLabel = 'Variação dos Produtos';

    public function form(Forms\Form $form): Forms\Form
    {
        $dayInSeconds = 86400;

        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('Nome da variação'))
                ->columnSpanFull()
                ->formatStateUsing(fn($state) => empty($state) ? $this->ownerRecord->name : $state)
                ->required(),

            Grid::make()
                ->schema([
                    Select::make('duration_time')
                        ->label(__('Tempo de duração'))
                        ->helperText(__('Esse campo define quanto tempo o usuário terá acesso ao produto'))
                        ->default(0)
                        ->options([
                            0 => __('Aquisição única'),
                            $dayInSeconds => __('1 dia'),
                            $dayInSeconds * 7 => __('1 Semana'),
                            $dayInSeconds * 15 => __('15 dias'),
                            $dayInSeconds * 30 => __('1 Mês'),
                            $dayInSeconds * 90 => __('3 Meses'),
                            $dayInSeconds * 365 => __('1 Ano'),
                        ]),

                    Forms\Components\TextInput::make('duration_time_seconds')
                        ->label(__('Tempo de duração em segundos'))
                        ->helperText(__('Esse campo define quanto tempo o usuário terá acesso ao produto, caso preenchido ele irá sobrepor o seletor ( Tempo de duração )'))
                        ->default(0)
                        ->formatStateUsing(fn($record) => $record->duration_time ?? 0)
                ]),

            Forms\Components\Grid::make()
                ->schema([
                    Grid::make()
                        ->schema([
                            Money::make('price')
                                ->label(__('Preço')),
                            Money::make('price_sale')
                                ->label(__('Preço Promoção')),
                        ]),
                ])
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('price'),
                Tables\Columns\TextColumn::make('price_sale'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->action(function (array $data) {

                        $notification = Notification::make();

                        $data['user_id'] = auth()->user()->id;
                        $data['parent_id'] = $this->ownerRecord->id;
                        $data['model_type'] = $this->ownerRecord->model_type;
                        $data['model_id'] = $this->ownerRecord->id;
                        $data['code'] = Str::uuid()->toString();

                        if (!empty($data['duration_time_seconds']) && $data['duration_time_seconds'] > 0) {
                            $data['duration_time'] = $data['duration_time_seconds'];
                        }

                        unset($data['duration_time_seconds']);

                        $result = Product::create($data);
                        if (!$result) {
                            $notification
                                ->title('Ops!')
                                ->body(__('Não foi possível salvar a variação, tente novamente...'))
                                ->danger()
                                ->send();
                        } else {
                            $notification
                                ->title(__('Sucesso'))
                                ->body(__('Variação criada com sucesso'))
                                ->success()
                                ->send();
                        }
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->action(function (array $data, $record) {
                        $notification = Notification::make();

                        if (!empty($data['duration_time_seconds']) && $data['duration_time_seconds'] > 0) {
                            $data['duration_time'] = $data['duration_time_seconds'];
                        }
                        unset($data['duration_time_seconds']);

                        $result = $record->update($data);

                        if (!$result) {
                            $notification
                                ->title('Ops!')
                                ->body(__('Não foi possível salvar a variação, tente novamente...'))
                                ->danger()
                                ->send();
                        } else {
                            $notification
                                ->title(__('Sucesso'))
                                ->body(__('Variação atualizada com sucesso'))
                                ->success()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}

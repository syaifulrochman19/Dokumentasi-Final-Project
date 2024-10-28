<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HargaAirPermeterResource\Pages;
use App\Filament\Resources\HargaAirPermeterResource\RelationManagers;
use App\Models\HargaAirPermeter;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Navigation\NavigationGroup;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class HargaAirPermeterResource extends Resource
{
    protected static ?string $model = HargaAirPermeter::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('harga')
                    ->required()
                    ->label('Harga Per Meter')
                    ->numeric()
                    ->unique(ignoreRecord: true)
                    ->rules([
                        fn(): Closure => function (string $attribute, $value, Closure $fail) {
                            if (!preg_match('/^\d+$/', $value)) {
                                $fail('Format :attribute tidak valid.');
                            }
                        },
                    ])
                    ->extraAttributes([
                        'pattern' => '[0-9]+', // Hanya angka
                        'title' => 'Hanya diperbolehkan angka',
                    ]),
                Forms\Components\TextInput::make('beban_meteran')
                    ->required()
                    ->label('Beban Meteran')
                    ->numeric()
                    ->rules([
                        fn(): Closure => function (string $attribute, $value, Closure $fail) {
                            if (!preg_match('/^\d+$/', $value)) {
                                $fail('Format :attribute tidak valid.');
                            }
                        },
                    ])
                    ->extraAttributes([
                        'pattern' => '[0-9]+',
                        'title' => 'Hanya diperbolehkan angka',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('harga')
                    ->numeric()
                    ->money('IDR', locale:'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('beban_meteran')
                    ->numeric()
                    ->money('IDR', locale:'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHargaAirPermeters::route('/'),
            'create' => Pages\CreateHargaAirPermeter::route('/create'),
            'edit' => Pages\EditHargaAirPermeter::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendudukResource\Pages;
use App\Filament\Resources\PendudukResource\RelationManagers;
use App\Models\Penduduk;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;
use Filament\Support\Contracts\HasColor;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\FieldGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Validation\Rules\Unique;

class PendudukResource extends Resource
{
    protected static ?string $model = Penduduk::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_penduduk')
                    ->required()
                    ->label('Nama Penduduk')
                    ->maxLength(255)
                    ->rules([
                        fn(): Closure => function (string $attribute, $value, Closure $fail) {
                            if (!preg_match('/^[a-zA-Z\s]+$/', $value)) {
                                $fail('Format :Attribute tidak valid.');
                            }
                            // // Cek keunikan nama_penduduk
                            // if (Penduduk::where('nama_penduduk', $value)->exists()) {
                            //     $fail('Format Nama Penduduk sudah ada.');
                            // }
                        },
                    ])
                    ->extraAttributes([
                        'pattern' => '[a-zA-Z\s]+', // Hanya huruf dan spasi
                        'title' => 'Hanya diperbolehkan huruf dan spasi',
                    ]),
                Forms\Components\TextInput::make('alamat')
                    ->required()
                    ->maxLength(255)
                    ->label('Alamat'),
                Forms\Components\TextInput::make('no_telepon')
                    ->required()
                    ->label('No Telepon')
                    ->maxLength(13)
                    ->minLength(12)
                    ->unique(ignoreRecord: true)
                    ->rules([
                        fn(): Closure => function (string $attribute, $value, Closure $fail) {
                            if (!preg_match('/^\d+$/', $value)) {
                                $fail('Format :Attribute tidak valid atau nomor telepon sudah ada.');
                            }
                            //tanyakan Bu Azizah 12-06-2024

                            //Cek keunikan no_telepon
                            // if (Penduduk::where('no_telepon', $value)->exists()) {
                            //     $fail('Format No Telepon sudah ada.');
                            // }

                        },
                    ])
                    // ->rules(function ($get) {
                    //     return [
                    //         'required',
                    //         'max:13',
                    //         'regex:/^[0-9]+$/',
                    //         Rule::unique('penduduks', 'no_telepon')
                    //             ->ignore($get('id')), // Mengabaikan rekaman yang sedang diedit berdasarkan ID
                    //     ];
                    // })
                    ->extraAttributes([
                        'pattern' => '[0-9]+', // Hanya angka
                        'title' => 'Hanya diperbolehkan angka',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_penduduk')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('alamat')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('no_telepon')
                    ->label('No Telepon')
                    ->sortable()
                    ->searchable(),
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
            'index' => Pages\ListPenduduks::route('/'),
            'create' => Pages\CreatePenduduk::route('/create'),
            'edit' => Pages\EditPenduduk::route('/{record}/edit'),
        ];
    }
}

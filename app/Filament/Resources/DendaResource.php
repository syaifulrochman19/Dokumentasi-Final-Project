<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DendaResource\Pages;
use App\Filament\Resources\DendaResource\RelationManagers;
use App\Models\Denda;
use App\Models\Kategori;
use App\Models\Penduduk;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as ComponentsSection;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class DendaResource extends Resource
{
    protected static ?string $model = Denda::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('penduduk_id')
                    ->relationship('penduduk', 'nama_penduduk')
                    ->required()
                    ->reactive()
                    ->options(Penduduk::all()->pluck('nama_penduduk', 'id'))
                    ->searchable(),
                Forms\Components\Fieldset::make('Identitas Pembayaran')
                    ->schema([
                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori')
                            ->relationship('kategori', 'nama')
                            ->required()
                            ->reactive()
                            ->options(Kategori::all()->pluck('nama', 'id'))
                            ->placeholder('Pilih Kategori')
                            ->afterStateUpdated(function ($state, callable $set) {
                                $kategori = Kategori::find($state);
                                if ($kategori) {
                                    $set('tipe_transaksi', $kategori->pemasukan ? 'Pemasukan' : 'Pengeluaran');
                                }
                            }),
                        Forms\Components\TextInput::make('tipe_transaksi')
                            ->label('Tipe Transaksi')
                            ->disabled()
                            ->dehydrated(false), // Do not save to database
                    ]),
                Forms\Components\Fieldset::make('Waktu Pembayaran')
                    ->schema([
                        Forms\Components\TextInput::make('jumlah_denda')
                            ->required()
                            ->maxValue(1000000)
                            ->numeric()
                            ->label('Jumlah Denda'),
                        Forms\Components\DatePicker::make('tanggal_denda')
                            ->required(),
                    ]),
                Forms\Components\Fieldset::make('Keterangan Pembayaran')
                    ->schema([
                        Forms\Components\TextInput::make('keterangan')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('bukti_denda')
                            ->label('Bukti Denda')
                            ->image()
                            ->helperText('Harap unggah file dengan format gambar.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('Nomor')
                    ->rowIndex(),
                Tables\Columns\ImageColumn::make('kategori.gambar')
                    ->label('Gambar'),
                Tables\Columns\TextColumn::make('kategori.nama')
                    ->numeric()
                    ->label('Kategori')
                    ->sortable()
                    ->description(function (Denda $record): string {
                        return $record->kategori->pemasukan ? 'Pemasukan' : 'Pengeluaran';
                    }),
                Tables\Columns\TextColumn::make('penduduk.nama_penduduk')
                    ->searchable()
                    ->label('Nama Penduduk')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_denda')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jumlah_denda')
                    ->numeric()
                    ->money('IDR', locale:'id')
                    ->sortable(),


                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable()
                    ->limit(20),
                Tables\Columns\ImageColumn::make('bukti_denda')
                    ,
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
                Filter::make('tanggal_denda')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_denda', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_denda', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('penduduk.nama_penduduk')->heading('Nama Penduduk'),
                                Column::make('kategori.nama')->heading('Kategori Transaksi'),
                                Column::make('tanggal_denda')->heading('Tanggal Denda'),
                                Column::make('keterangan')->heading('Keterangan'),
                                Column::make('jumlah_denda')->heading('Jumlah Denda'),
                                // Tambahkan kolom-kolom lainnya sesuai kebutuhan
                            ])
                            ->withFilename(function () {
                                // Set timezone ke waktu yang sesuai
                                date_default_timezone_set('Asia/Jakarta');
                                return 'Denda-' . date('d-m-Y-H-i');
                            })
                    ]),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                ComponentsSection::make('Informasi Pengeluaran')
                    ->schema([
                        TextEntry::make('penduduk.nama_penduduk')->label('Nama Penduduk'),
                        TextEntry::make('kategori.nama')->label('Kategori Transaksi'),
                        TextEntry::make('jenis_transaksi')->label('Jenis Transaksi'),
                        TextEntry::make('jumlah_denda'),
                        TextEntry::make('tanggal_denda'),
                        TextEntry::make('keterangan')
                            ->formatStateUsing(function ($state) {
                                if ($state) {
                                    $filename = basename($state);
                                    $url = Storage::url($state);
                                    return "<a href='{$url}' target='_blank'>{$filename}</a>";
                                }
                                return '-';
                            })
                            ->html(),
                    ])->columns(2)
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
            'index' => Pages\ListDendas::route('/'),
            'create' => Pages\CreateDenda::route('/create'),
            'edit' => Pages\EditDenda::route('/{record}/edit'),
        ];
    }
}

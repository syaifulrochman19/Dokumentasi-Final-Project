<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiResource\Pages;
use App\Filament\Resources\TransaksiResource\RelationManagers;
use App\Models\Kategori;
use App\Models\Tagihan;
use App\Models\Transaksi;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use PhpParser\Node\Stmt\Label;
use stdClass;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Closure;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static ?int $navigationSort = 3;

    // Menambahkan label atau navigationLabel untuk mengubah nama di sidebar
    protected static ?string $label = 'Transaksi Pajak'; // Ini akan mengubah label di beberapa tempat
    protected static ?string $navigationLabel = 'Transaksi Pajak'; // Ini khusus untuk navigation sidebar

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Fieldset::make('Input Pajak Penduduk')
                ->schema([
                    Forms\Components\Select::make('tagihan_id')
                        ->label('Tagihan')
                        ->relationship('tagihan', 'id', function (Builder $query) {
                            $query->with(['penduduk']);
                        })
                        ->required()
                        ->reactive()
                        ->searchable()
                        ->unique(ignoreRecord: true)
                        ->options(function () {
                            return Tagihan::with('penduduk')
                                ->get()
                                ->mapWithKeys(function ($tagihan) {
                                    return [
                                        $tagihan->id => $tagihan->penduduk->nama_penduduk . ' - ' . $tagihan->bulan_tagihan . ' ' . $tagihan->tahun_tagihan,
                                    ];
                                });
                        })
                        ->getSearchResultsUsing(function (string $search) {
                            return Tagihan::query()
                                ->whereHas('penduduk', function (Builder $query) use ($search) {
                                    $query->where('nama_penduduk', 'like', "%{$search}%");
                                })
                                ->get()
                                ->mapWithKeys(function ($tagihan) {
                                    return [
                                        $tagihan->id => $tagihan->penduduk->nama_penduduk . ' - ' . $tagihan->bulan_tagihan . ' ' . $tagihan->tahun_tagihan,
                                    ];
                                });
                        })
                        ->getOptionLabelUsing(function ($value) {
                            $tagihan = Tagihan::find($value);
                            return $tagihan ? $tagihan->penduduk->nama_penduduk . ' - ' . $tagihan->bulan_tagihan . ' ' . $tagihan->tahun_tagihan : null;
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            $tagihan = Tagihan::find($state);
                            if ($tagihan) {
                                $set('tagihan_meteran', $tagihan->tagihan_meteran);
                                $set('total_tagihan', $tagihan->total_tagihan);
                                $set('status_tagihan', $tagihan->status_tagihan);

                                // Set total_pembayaran to total_tagihan
                                $set('jumlah_pembayaran', $tagihan->total_tagihan);
                            }
                        }),
                    Forms\Components\Select::make('kategori_id')
                        ->label('Kategori')
                        ->relationship('kategori', 'nama')
                        ->required()
                        ->reactive()
                        // ->options(Kategori::all()->pluck('nama', 'id'))
                        ->options(Kategori::where('pemasukan', true)->pluck('nama', 'id')) // Menambahkan kondisi di sini
                        ->afterStateUpdated(function ($state, callable $set) {
                            $kategori = Kategori::find($state);
                            if ($kategori) {
                                $set('tipe_transaksi', $kategori->pemasukan ? 'Pemasukan' : 'Pengeluaran');
                            }
                        }),
                ]),
            Forms\Components\Fieldset::make('Total Pajak')
                ->schema([
                    Forms\Components\TextInput::make('jumlah_pembayaran')
                        ->required()
                        ->numeric()
                        ->readOnly()
                        ->label('Jumlah Pembayaran'),
                    Forms\Components\TextInput::make('total_tagihan')
                        ->label('Total Tagihan')
                        ->numeric()
                        ->disabled(),

                ]),
            Forms\Components\TextInput::make('tagihan_meteran')
                ->label('Tagihan Meteran')
                ->numeric()
                ->disabled(),
            Forms\Components\TextInput::make('tipe_transaksi')
                ->label('Tipe Transaksi')
                ->disabled()
                ->dehydrated(false),
            Forms\Components\Fieldset::make('Pelunasan Pajak')
                ->schema([
                    Forms\Components\DatePicker::make('tanggal_pembayaran')
                        ->required(),
                    Forms\Components\Select::make('status_tagihan')
                        ->label('Status Tagihan')
                        ->options([
                            'Belum Lunas' => 'Belum Lunas',
                            'Lunas' => 'Lunas',
                        ])
                        ->default('Lunas')
                        ->disabled()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            $tagihan = Tagihan::find($get('tagihan_id'));
                            if ($tagihan) {
                                $tagihan->update(['status_tagihan' => $state]);
                            }
                        }),
                ]),
            Forms\Components\Fieldset::make('Keterangan Pajak')
                ->schema([
                    Forms\Components\TextInput::make('keterangan')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\FileUpload::make('bukti_pembayaran')
                        ->label('Bukti Pembayaran')
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
                    ->sortable()
                    ->description(function (Transaksi $record): string {
                        return $record->kategori->pemasukan ? 'Pemasukan' : 'Pengeluaran';
                    }),
                Tables\Columns\TextColumn::make('tagihan.penduduk.nama_penduduk')
                    ->label('Nama Penduduk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tagihan.bulan_tagihan')
                    ->label('Bulan Tagihan')
                    ->sortable()
                    ->description(fn (Transaksi $record): string => $record->tagihan->tahun_tagihan),
                Tables\Columns\TextColumn::make('tanggal_pembayaran')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tagihan.total_tagihan')
                    ->numeric()
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->description(fn (Transaksi $record): string =>'Total Meteran :' . $record->tagihan->tagihan_meteran),
                Tables\Columns\TextColumn::make('jumlah_pembayaran')
                    ->numeric()
                    ->money('IDR', locale:'id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable()
                    ->limit(20)
                    ->description(fn (Transaksi $record): string =>'Status : ' . $record->tagihan->status_tagihan),
                Tables\Columns\ImageColumn::make('bukti_pembayaran'),
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
                Filter::make('tanggal_pembayaran')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_pembayaran', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_pembayaran', '<=', $date),
                            );
                    })
                ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('downloadNota')
                    ->label('Download Nota')
                    ->url(fn(Transaksi $record) => route('download.transaksi' ,['id' => $record->id]))
                    ->icon('heroicon-m-document-arrow-down'),
                ])


            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('tagihan.penduduk.nama_penduduk')->heading('Nama Penduduk'),
                                Column::make('kategori.nama')->heading('Kategori Transaksi'),
                                Column::make('tagihan.bulan_tagihan')->heading('Bulan Tagihan'),
                                Column::make('tagihan.tahun_tagihan')->heading('Tahun Tagihan'),
                                Column::make('tanggal_pembayaran')->heading('Tanggal Pembayaran'),
                                Column::make('jumlah_pembayaran')->heading('Jumlah Pembayaran'),
                                // Tambahkan kolom-kolom lainnya sesuai kebutuhan
                            ])
                            ->withFilename(function () {
                                // Set timezone ke waktu yang sesuai
                                date_default_timezone_set('Asia/Jakarta');
                                return 'Transaksi-' . date('d-m-Y-H-i');
                            })
                    ]),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Validator::make($data, [
            'tagihan_id' => [
                'required',
                Rule::unique('transaksis')->where(function ($query) use ($data) {
                    return $query->where('tagihan_id', $data['tagihan_id']);
                }),
            ],
            // Tambahkan validasi lain sesuai kebutuhan
        ])->validate();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tagihanId = $data['tagihan_id'] ?? null;
        $transaksiId = $data['id'] ?? null;

        Validator::make($data, [
            'tagihan_id' => [
                'required',
                Rule::unique('transaksis')->where(function ($query) use ($tagihanId) {
                    return $query->where('tagihan_id', $tagihanId);
                })->ignore($transaksiId),
            ],
            // Tambahkan validasi lain sesuai kebutuhan
        ])->validate();

        return $data;
    }


    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                ComponentsSection::make('Informasi Transaksi')
                    ->schema([
                        TextEntry::make('tagihan.penduduk.nama_penduduk')->label('Nama Penduduk'),
                        TextEntry::make('tagihan.bulan_tagihan')->label('Bulan Tagihan'),
                        TextEntry::make('tagihan.tahun_tagihan')->label('Tahun Tagihan'),
                        TextEntry::make('tagihan.tagihan_meteran')->label('Tagihan Meteran'),
                        TextEntry::make('tagihan.total_tagihan')->label('Total Tagihan'),
                        TextEntry::make('kategori.nama')->label('Kategori Transaksi'),
                        TextEntry::make('jenis_transaksi')->label('Jenis Transaksi'), // properti terhitung baru
                        TextEntry::make('jumlah_pembayaran'),
                        TextEntry::make('tanggal_pembayaran'),
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
            'index' => Pages\ListTransaksis::route('/'),
            'create' => Pages\CreateTransaksi::route('/create'),
            'edit' => Pages\EditTransaksi::route('/{record}/edit'),
        ];
    }
}

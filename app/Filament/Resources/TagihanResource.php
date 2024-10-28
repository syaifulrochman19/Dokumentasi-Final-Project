<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagihanResource\Pages;
use App\Filament\Resources\TagihanResource\RelationManagers;
use App\Models\HargaAirPermeter;
use App\Models\Penduduk;
use App\Models\Tagihan;
use Closure;
use Doctrine\Inflector\Rules\Turkish\Rules;
use Dotenv\Exception\ValidationException;
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
use Illuminate\Support\Facades\Validator;
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;



class TagihanResource extends Resource
{
    protected static ?string $model = Tagihan::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?int $navigationSort = 2;

    // Menambahkan label atau navigationLabel untuk mengubah nama di sidebar
    protected static ?string $label = 'Tagihan Pajak'; // Ini akan mengubah label di beberapa tempat
    protected static ?string $navigationLabel = 'Tagihan Pajak'; // Ini khusus untuk navigation sidebar

    public static function form(Form $form): Form
    {
        $hargaOptions = HargaAirPermeter::pluck('harga', 'id')->toArray();
        $bebanOptions = HargaAirPermeter::pluck('beban_meteran', 'beban_meteran')->unique()->toArray();
        return $form
            ->schema([
                Forms\Components\Select::make('penduduk_id')
                    ->relationship('penduduk', 'nama_penduduk')
                    ->required()
                    ->reactive()
                    ->options(Penduduk::all()->pluck('nama_penduduk', 'id'))
                    ->searchable()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        self::updateMeteranAwal($get, $set);
                    }),

                Forms\Components\Placeholder::make('penduduk_deskripsi')
                    ->label('')
                    ->content(fn (callable $get) => $get('penduduk_deskripsi'))
                    ->extraAttributes(['style' => 'margin-top: -10px; margin-bottom: 10px; color: grey;']),
                Forms\Components\Fieldset::make('Periode Tagihan')
                    ->schema([
                        Select::make('bulan_tagihan')
                            ->label('Bulan Tagihan')
                            ->required()
                            ->options([
                                'Januari' => 'Januari',
                                'Februari' => 'Februari',
                                'Maret' => 'Maret',
                                'April' => 'April',
                                'Mei' => 'Mei',
                                'Juni' => 'Juni',
                                'Juli' => 'Juli',
                                'Agustus' => 'Agustus',
                                'September' => 'September',
                                'Oktober' => 'Oktober',
                                'November' => 'November',
                                'Desember' => 'Desember',
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                self::updateMeteranAwal($get, $set);
                            }),
                        Select::make('tahun_tagihan')
                        //Tambahkan Pluck untuk search Tahun
                            ->label('Tahun Tagihan')
                            ->required()
                            ->options(array_combine(range(2024, 2070), range(2024, 2070)))
                            ->reactive()
                            ->searchable()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                self::updateMeteranAwal($get, $set);
                            }),
                    ]),
                    Forms\Components\Fieldset::make('Selisih Meteran')
                    ->schema([
                        Forms\Components\TextInput::make('meteran_awal')
                        ->required()
                        ->label('Meteran Awal')
                        ->maxValue(10000)
                        ->numeric()
                        ->reactive()
                        // ->readOnly(fn (callable $get) => $get('meteran_awal_readonly'))
                        ->rules([
                            fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                if (!preg_match('/^\d+$/', $value)) {
                                    $fail('Pastikan bulan tagihan yang dipilih benar.');
                                }
                            },
                        ])
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $meteranAkhir = $get('meteran_akhir');
                            if ($meteranAkhir !== null) {
                                $set('tagihan_meteran', intval($meteranAkhir) - intval($state));
                            }
                        })
                        ->extraAttributes([
                            'pattern' => '[0-9]+', // Hanya angka
                            'title' => 'Pastikan bulan tagihan benar',
                        ]),


                        Forms\Components\TextInput::make('meteran_akhir')
                        ->required()
                        ->label('Meteran Akhir')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10000)
                        ->reactive()
                        // ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        //     $meteranAwal = $get('meteran_awal');
                        //     if ($meteranAwal !== null) {
                        //         $tagihanMeteran = intval($state) - intval($meteranAwal);
                        //         $set('tagihan_meteran', $tagihanMeteran);

                        //         $hargaAirPerMeterId = $get('harga_air_permeter_id');
                        //         $hargaAirPerMeter = HargaAirPermeter::find($hargaAirPerMeterId);

                        //         if ($hargaAirPerMeter !== null) {
                        //             $totalTagihan = $tagihanMeteran * $hargaAirPerMeter->harga;
                        //             $set('total_tagihan', $totalTagihan);
                        //         }
                        //     }
                        // })
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $meteranAwal = $get('meteran_awal');
                            if ($meteranAwal !== null) {
                                $tagihanMeteran = intval($state) - intval($meteranAwal);
                                $set('tagihan_meteran', $tagihanMeteran);

                                $hargaAirPerMeterId = $get('harga_air_permeter_id');
                                $hargaAirPerMeter = HargaAirPermeter::find($hargaAirPerMeterId);

                                if ($hargaAirPerMeter !== null) {
                                    $bebanMeteran = intval($get('beban_meteran'));
                                    $totalTagihan = ($tagihanMeteran * $hargaAirPerMeter->harga) + $bebanMeteran;
                                    $set('total_tagihan', $totalTagihan);
                                }
                            }
                        })
                        ->extraAttributes([
                            'pattern' => '[0-9]+', // Hanya angka
                            'title' => 'Pastikan meteran akhir benar',
                        ]),
                    ]),
                    Forms\Components\Fieldset::make('Harga Tagihan')
                    ->schema([
                        Forms\Components\TextInput::make('tagihan_meteran')
                            ->required()
                            ->label('Tagihan Meteran')
                            ->numeric()
                            ->dehydrated(false) // Mengatur input agar tidak dikirimkan saat form disubmit
                            ->disabled()
                            ->rules([
                                fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                    if ($value <= 0) {
                                        $fail('Format :Attribute tidak valid. Pastikan Meteran Akhir Benar');
                                    }
                                },
                            ])
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $meteranAwal = intval($get('meteran_awal')); // Konversi ke tipe data integer
                                $meteranAkhir = intval($get('meteran_akhir')); // Konversi ke tipe data integer
                                $hargaAirPerMeter = HargaAirPermeter::find($state);

                                if ($meteranAwal !== null && $meteranAkhir !== null && $hargaAirPerMeter !== null) {
                                    $bebanMeteran = intval($get('beban_meteran'));
                                    $tagihanMeteran = $meteranAkhir - $meteranAwal;
                                    $totalTagihan = ($tagihanMeteran * $hargaAirPerMeter->harga) + $bebanMeteran;
                                    $set('total_tagihan', $totalTagihan);
                                }
                            }),
                        Forms\Components\Select::make('harga_air_permeter_id')
                            ->label('Harga Per Meter')
                            ->relationship('hargaAirPermeter', 'harga')
                            // ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $hargaAirPerMeterId = $state;
                                $hargaAirPerMeter = HargaAirPermeter::find($hargaAirPerMeterId);

                                if ($hargaAirPerMeter !== null) {
                                    $set('beban_meteran', $hargaAirPerMeter->beban_meteran);
                                    $meteranAwal = intval($get('meteran_awal'));
                                    $meteranAkhir = intval($get('meteran_akhir'));
                                    if ($meteranAwal !== null && $meteranAkhir !== null) {
                                        $bebanMeteran = intval($get('beban_meteran'));
                                        $tagihanMeteran = ($meteranAkhir - $meteranAwal);
                                        $totalTagihan = ($tagihanMeteran * $hargaAirPerMeter->harga) + $bebanMeteran;
                                        $set('total_tagihan', $totalTagihan);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('beban_meteran')
                            ->label('Beban Meteran')
                            ->options($bebanOptions)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $hargaAirPerMeterId = $get('harga_air_permeter_id');
                                $hargaAirPerMeter = HargaAirPermeter::find($hargaAirPerMeterId);

                                if ($hargaAirPerMeter !== null) {
                                    $meteranAwal = intval($get('meteran_awal'));
                                    $meteranAkhir = intval($get('meteran_akhir'));
                                    if ($meteranAwal !== null && $meteranAkhir !== null) {
                                        $bebanMeteran = intval($state);
                                        $tagihanMeteran = ($meteranAkhir - $meteranAwal);
                                        $totalTagihan = ($tagihanMeteran * $hargaAirPerMeter->harga) + $bebanMeteran;
                                        $set('total_tagihan', $totalTagihan);
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('total_tagihan')
                            ->required()
                            ->label('Total Tagihan')
                            ->numeric()
                            ->readOnly()
                            ->rules([
                                fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                    if ($value <= 0) {
                                        $fail('Format :Attribute tidak valid. Pastikan Meteran Akhir Benar');
                                    }
                                },
                            ]),
                        ]),
                Forms\Components\Select::make('status_tagihan')
                    ->options([
                        'Belum Lunas' => 'Belum Lunas',
                        'Lunas' => 'Lunas',
                    ])
                    ->default('Belum Lunas')
                    ->disabled()

                    ->required()
                    ->label('Status'),
            ]);
    }

    protected static function updateMeteranAwal(callable $get, callable $set)
    {
        $pendudukId = $get('penduduk_id');
        $bulan = $get('bulan_tagihan');
        $tahun = $get('tahun_tagihan');

        // $isReadOnly = false;

        if ($pendudukId && $bulan && $tahun) {
            $bulanAngka = self::convertBulanToAngka($bulan);

            // Jika bulan yang dipilih adalah Januari
            if ($bulan === 'Januari') {
                // Ambil meteran akhir dari Desember tahun sebelumnya
                $lastTagihan = Tagihan::where('penduduk_id', $pendudukId)
                    ->where('tahun_tagihan', $tahun - 1) // Tahun sebelumnya
                    ->where('bulan_tagihan', 'Desember') // Bulan Desember
                    ->orderBy('tahun_tagihan', 'desc')
                    ->orderByRaw("FIELD(bulan_tagihan, 'Desember', 'November',
                    'Oktober', 'September', 'Agustus', 'Juli', 'Juni', 'Mei',
                    'April', 'Maret', 'Februari', 'Januari')")
                    ->first();

                if ($lastTagihan) {
                    $set('meteran_awal', $lastTagihan->meteran_akhir);
                    // $isReadOnly = true;
                } else {
                    // Jika tidak ada tagihan sebelumnya, set meteran awal menjadi null
                    $set('meteran_awal', null);
                }
            } else {
                // Jika bulan yang dipilih bukan Januari, gunakan logika yang sudah ada
                // Mengambil tagihan terakhir dari penduduk dengan penduduk_id dan sebelum bulan dan tahun ini
                    $lastTagihan = Tagihan::where('penduduk_id', $pendudukId)
                    ->where(function ($query) use ($tahun, $bulanAngka) {
                        $query->where('tahun_tagihan', $tahun)
                            ->whereRaw("FIELD(bulan_tagihan, 'Januari', 'Februari',
                            'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
                            'September', 'Oktober', 'November', 'Desember') < ?", [$bulanAngka]);
                    })
                    ->orderBy('tahun_tagihan', 'desc')
                    ->orderByRaw("FIELD(bulan_tagihan, 'Desember', 'November',
                    'Oktober', 'September', 'Agustus', 'Juli', 'Juni', 'Mei',
                    'April', 'Maret', 'Februari', 'Januari')")
                    ->first();

                if ($lastTagihan) {
                    $bulanSebelumnya = $lastTagihan->bulan_tagihan;
                    $tahunSebelumnya = $lastTagihan->tahun_tagihan;
                    $bulanSebelumnyaAngka = self::convertBulanToAngka($bulanSebelumnya);

                    // Pastikan bulan sebelumnya ada di tahun yang sama
                    if ($bulanAngka === $bulanSebelumnyaAngka + 1 && $tahun === $tahunSebelumnya) {
                        $set('meteran_awal', $lastTagihan->meteran_akhir);
                        // $isReadOnly = true;
                    } else {
                        // Jika bulan yang dipilih tidak berurutan dengan bulan tagihan sebelumnya,
                        // maka atur nilai 'meteran_awal' menjadi null dan tampilkan pesan error
                        $set('meteran_awal', null);
                    }
                } else {
                    $set('meteran_awal', null);
                }
            }
        }
        // $set('meteran_awal_readonly', $isReadOnly);
    }

    protected static function convertBulanToAngka($bulan)
    {
        $bulanMap = [
            'Januari' => 1,
            'Februari' => 2,
            'Maret' => 3,
            'April' => 4,
            'Mei' => 5,
            'Juni' => 6,
            'Juli' => 7,
            'Agustus' => 8,
            'September' => 9,
            'Oktober' => 10,
            'November' => 11,
            'Desember' => 12,
        ];
        return $bulanMap[$bulan];
    }
    protected static function hitungTotalTagihan($meteranAwal, $meteranAkhir, $hargaAirPerMeter)
    {
        $meteranAwal = intval($meteranAwal);
        $meteranAkhir = intval($meteranAkhir);

        if ($meteranAwal !== null && $meteranAkhir !== null && $hargaAirPerMeter !== null) {
            $tagihanMeteran = $meteranAkhir - $meteranAwal;
            return $tagihanMeteran * $hargaAirPerMeter->harga;
        }
        return null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('Nomor')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('penduduk.nama_penduduk')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Tagihan $record): string => 'No Telepon: ' . $record->penduduk->no_telepon),
                Tables\Columns\TextColumn::make('bulan_tagihan')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Tagihan $record): string => $record->tahun_tagihan),
                // Tables\Columns\TextColumn::make('meteran_awal')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('meteran_akhir')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('tagihan_meteran')
                //     ->numeric()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('total_tagihan')
                    ->numeric()
                    ->money('IDR', locale:'id')
                    ->sortable()
                    ->description(fn (Tagihan $record): string => 'Total Meteran :'.$record->tagihan_meteran),
                Tables\Columns\TextColumn::make('status_tagihan')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'Belum Lunas' => 'danger',
                        'Lunas' => 'success',
                    }),
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
                SelectFilter::make('status_tagihan')
                ->label('Status Tagihan')
                ->options([
                    'Belum Lunas' => 'Belum Lunas',
                    'Lunas' => 'Lunas'
                ]),
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
                                Column::make('bulan_tagihan')->heading('Bulan Tagihan'),
                                Column::make('total_tagihan')->heading('Total Tagihan'),
                                Column::make('status_tagihan')->heading('Status Tagihan'),

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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                ComponentsSection::make('Informasi Transaksi')
                    ->schema([
                        TextEntry::make('penduduk.nama_penduduk')->label('Nama Penduduk'),
                        TextEntry::make('bulan_tagihan')->label('Bulan Tagihan'),
                        TextEntry::make('tahun_tagihan')->label('Tahun Tagihan'),
                        TextEntry::make('meteran_awal')->label('Meteran Awal'),
                        TextEntry::make('meteran_akhir')->label('Meteran Akhir'),
                        TextEntry::make('tagihan_meteran')->label('Tagihan Meteran'),
                        TextEntry::make('total_tagihan')->label('Total Tagihan'),
                        TextEntry::make('status_tagihan')->label('Status Tagihan'),
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
            'index' => Pages\ListTagihans::route('/'),
            'create' => Pages\CreateTagihan::route('/create'),
            'edit' => Pages\EditTagihan::route('/{record}/edit'),
        ];
    }

}

<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Expense;
use App\Models\NotaDinas;
use App\Models\NotaDinasDetail;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Prospect;
use App\Models\Vendor;
use App\Support\CompanySubscription;
use App\Support\Rupiah;
use App\Support\UserVisibility;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderForm
{
    /**
     * Prefix nomor proyek dari inisial_wo company (fallback MW).
     */
    public static function orderNumberPrefix(): string
    {
        $raw = (string) (CompanySubscription::company()?->inisial_wo ?: 'MW');
        $prefix = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $raw));

        return $prefix !== '' ? $prefix : 'MW';
    }

    /**
     * Nomor proyek unik: {INISIAL_WO}-{6 digit}.
     */
    public static function defaultOrderNumber(): string
    {
        $prefix = static::orderNumberPrefix();

        do {
            $number = $prefix.'-'.random_int(100000, 999999);
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }

    /**
     * Scope pilihan AM/EM: prefer role khusus bila ada di tim, else semua user company.
     */
    public static function constrainTeamRoleQuery(Builder $query, string $preferredRole): Builder
    {
        UserVisibility::constrainUsersQuery($query);

        $preferred = (clone $query)->role($preferredRole);
        if ($preferred->exists()) {
            return $query->role($preferredRole);
        }

        return $query;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make('Informasi Proyek')
                    ->icon('heroicon-o-information-circle')
                    ->description('Detail dasar proyek')
                    ->schema([
                        TextInput::make('number')
                            ->default(fn (): string => static::defaultOrderNumber())
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->maxLength(32)
                            ->unique(Order::class, 'number', ignoreRecord: true)
                            ->helperText(fn (): string => 'Otomatis dari inisial WO perusahaan ('.static::orderNumberPrefix().').'),
                        Select::make('prospect_id')
                            ->options(function (Get $get, ?Order $record) {
                                if ($record && $record->exists) {
                                    $currentId = $record->prospect_id ?? $get('prospect_id');
                                    $currentName = $record->prospect?->name_event ?? Prospect::find($currentId)?->name_event;

                                    return $currentId ? [$currentId => ($currentName ?? (string) $currentId)] : [];
                                }

                                $currentId = $get('prospect_id');
                                $query = Prospect::query();
                                UserVisibility::constrainOwnedQuery($query, 'user_id');
                                $query->where(function (Builder $q) use ($currentId) {
                                    $q->whereDoesntHave('orders', function (Builder $orders) {
                                        $orders->whereNotNull('status');
                                    });
                                    if ($currentId) {
                                        $q->orWhere('id', $currentId);
                                    }
                                });

                                return $query->pluck('name_event', 'id')->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->unique(Order::class, 'prospect_id', ignoreRecord: true)
                            ->label('Prospek')
                            ->debounce(500)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $prospect = Prospect::find($state);
                                    if ($prospect) {
                                        $set('name', $prospect->name_event);
                                        $set('slug', Str::slug($prospect->name_event));
                                    } else {
                                        $set('name', null);
                                        $set('slug', null);
                                    }
                                } else {
                                    $set('name', null);
                                    $set('slug', null);
                                }
                            })
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),
                        TextInput::make('name')
                            ->required()
                            ->readOnly()
                            ->label('Nama Acara')
                            ->debounce(500),
                        Select::make('user_id')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => static::constrainTeamRoleQuery($query, 'Account Manager'),
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => Auth::id())
                            ->label('Account Manager')
                            ->helperText(fn (): string => UserVisibility::isSingleSeatPlan()
                                ? 'Paket 1 seat: pilih akun Anda sendiri sebagai penanggung jawab proyek.'
                                : 'Pilih Account Manager dari tim Anda. Jika belum ada role AM, semua anggota tim ditampilkan.'),
                        TextInput::make('slug')
                            ->readOnly()
                            ->maxLength(255),
                        Select::make('employee_id')
                            ->relationship(
                                name: 'employee',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => static::constrainTeamRoleQuery($query, 'Event Manager'),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => Auth::id())
                            ->label('Event Manager')
                            ->helperText(fn (): string => UserVisibility::isSingleSeatPlan()
                                ? 'Paket 1 seat: pilih akun Anda sendiri sebagai Event Manager.'
                                : 'Pilih Event Manager dari tim Anda. Jika belum ada role EM, semua anggota tim ditampilkan.'),
                        TextInput::make('no_kontrak')
                            ->required()
                            ->label('No. Kontrak')
                            ->maxLength(255)
                            ->helperText(fn (): string => 'Nomor kontrak proyek. Inisial kontrak company: '
                                .(CompanySubscription::company()?->inisial_kontak ?: 'KKP').'.'),
                        TextInput::make('pax')
                            ->required()
                            ->label('Pax')
                            ->default(1000)
                            ->numeric(),
                        FileUpload::make('doc_kontrak')
                            ->label('Upload Kontrak')
                            ->reorderable()
                            ->required()
                            ->helperText('Pastikan kontrak sudah ditandatangani semua pihak.')
                            ->openable()
                            ->directory('doc_kontrak')
                            ->downloadable()
                            ->acceptedFileTypes(['application/pdf']),
                        FileUpload::make('agreement_product')
                            ->label('File Persetujuan Produk')
                            ->reorderable()
                            ->required()
                            ->helperText('Pastikan file persetujuan produk sudah ditandatangani.')
                            ->openable()
                            ->directory('agreement_product')
                            ->downloadable()
                            ->acceptedFileTypes(['application/pdf']),
                        ToggleButtons::make('status')
                            ->inline()
                            ->options(OrderStatus::class)
                            ->label('Status Pesanan')
                            ->columnSpan(2)
                            ->required()
                            ->helperText('Status Done: Finance hanya bisa view, Super Admin bisa edit.'),
                        RichEditor::make('note')
                            ->label('Keterangan Tambahan')
                            ->fileAttachmentsDirectory('orders')
                            ->columnSpan(3)
                            ->fileAttachmentsDisk('public'),
                    ]),
                Step::make('Detail Pembayaran')
                    ->icon('heroicon-o-currency-dollar')
                    ->description('Produk dan informasi pembayaran')
                    ->schema([
                        Section::make('Product dipesan')
                            ->schema([OrderResource::getItemsRepeater()])
                            ->columnSpanFull(),
                        Section::make('Data Pembayaran')
                            ->schema([
                                Repeater::make('Jika Ada Pembayaran')
                                    ->relationship('dataPembayaran')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('keterangan')
                                                ->label('Keterangan')
                                                ->prefix('Pembayaran')
                                                ->required()
                                                ->placeholder('1, 2, 3 dst'),
                                            Select::make('payment_method_id')
                                                ->relationship('paymentMethod', 'name')
                                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->is_cash ? 'Kas/Tunai' : ($record->bank_name ? "{$record->bank_name} - {$record->no_rekening}" : $record->name))
                                                ->required()
                                                ->label('Metode Pembayaran'),
                                            TextInput::make('nominal')
                                                ->prefix('Rp. ')
                                                ->label('Nominal')
                                                ->required()
                                                ->mask(RawJs::make('$money($input)'))
                                                ->stripCharacters(',')
                                                ->debounce(800)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    // Hanya sanitasi nilai item. Hitung ulang bayar/sisa
                                                    // dilakukan di afterStateUpdated repeater (konteks parent).
                                                    if ($state === null) {
                                                        return;
                                                    }

                                                    $sanitized = is_numeric($state)
                                                        ? (int) $state
                                                        : (int) preg_replace('/[^\d]/', '', (string) $state);
                                                    $set('nominal', $sanitized);
                                                }),
                                            Select::make('kategori_transaksi')
                                                ->options([
                                                    'uang_masuk' => 'Uang Masuk',
                                                    'uang_keluar' => 'Uang Keluar',
                                                ])
                                                ->default('uang_masuk')
                                                ->label('Tipe Transaksi')
                                                ->required(),
                                            DatePicker::make('tgl_bayar')
                                                ->date()
                                                ->required()
                                                ->label('Tgl. Bayar')
                                                ->live(onBlur: true),
                                            FileUpload::make('image')
                                                ->label('Payment Proof')
                                                ->image()
                                                ->maxSize(1280)
                                                ->disk('public')
                                                ->directory('payment-proofs/'.date('Y/m'))
                                                ->visibility('public')
                                                ->downloadable()
                                                ->openable()
                                                ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                                ->helperText('Max 1MB. JPG or PNG only.'),
                                        ]),
                                    ])
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        OrderResource::updateDependentFinancialFields($get, $set);
                                    })
                                    ->addActionLabel('Tambah Pembayaran')
                                    ->label('Pembayaran')
                                    ->collapsed()
                                    ->itemLabel(
                                        function (array $state): ?string {
                                            $keterangan = $state['keterangan'] ?? 'Pembayaran';
                                            $tglRaw = $state['tgl_bayar'] ?? null;
                                            $tanggal = $tglRaw ? \Illuminate\Support\Carbon::parse($tglRaw)->format('d M Y') : 'Tanggal?';
                                            $nominalRaw = $state['nominal'] ?? 0;
                                            $nominalVal = is_numeric($nominalRaw)
                                                ? (int) $nominalRaw
                                                : (int) preg_replace('/[^\d]/', '', (string) $nominalRaw);
                                            $nominalFmt = 'Rp. '.number_format($nominalVal, 0, '.', ',');

                                            $methodLabel = 'Metode?';
                                            try {
                                                if (isset($state['payment_method_id']) && $state['payment_method_id']) {
                                                    $pm = \App\Models\PaymentMethod::find($state['payment_method_id']);
                                                    if ($pm) {
                                                        $methodLabel = $pm->is_cash
                                                            ? 'Kas/Tunai'
                                                            : ($pm->bank_name ? "{$pm->bank_name} - {$pm->no_rekening}" : $pm->name);
                                                    }
                                                }
                                            } catch (\Exception $e) {
                                            }

                                            return "{$keterangan} | {$tanggal} | {$methodLabel} | {$nominalFmt}";
                                        }
                                    ),
                            ])
                            ->columnSpanFull(),
                        TextInput::make('total_price')
                            ->prefix('Rp. ')
                            ->label('Total Paket Awal')
                            ->readOnly()
                            ->default(0)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(','),
                        Hidden::make('is_cash')
                            ->dehydrated(false),
                        TextInput::make('promo')
                            ->default(0)
                            ->prefix('Rp. ')
                            ->readOnly()
                            ->label('Promo')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state)),
                        TextInput::make('penambahan')
                            ->default(0)
                            ->prefix('Rp. ')
                            ->readOnly()
                            ->label('Penambahan Harga')
                            ->helperText('Auto-calculated from selected products penambahan publish price')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state)),
                        TextInput::make('pengurangan')
                            ->default(0)
                            ->prefix('Rp. ')
                            ->label('Total Pengurangan dari Produk (Otomatis)')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state))
                            ->readOnly()
                            ->helperText('Nilai ini dihitung otomatis dari total pengurangan semua produk dalam order.'),
                    ]),
                Step::make('Informasi Keuangan')
                    ->icon('heroicon-o-banknotes')
                    ->description('Catat detail keuangan')
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('bayar')
                                    ->label('Uang dibayar')
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Pembayaran klien ke rekening perusahaan')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state)),
                                TextInput::make('grand_total')
                                    ->label('Grand Total')
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Grand Total = Total Paket + Penambahan - Promo - Pengurangan')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state)),
                                TextInput::make('tot_pengeluaran')
                                    ->label('Pengeluaran')
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Total pembayaran ke vendor')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state)),
                                TextInput::make('sisa')
                                    ->label('Sisa Pembayaran')
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Sisa yang masih harus dibayar klien')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state)),
                                TextInput::make('laba_kotor')
                                    ->label('Laba Kotor')
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Grand total - Pembayaran ke vendor')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state)),
                                TextInput::make('uang_diterima')
                                    ->label('Uang Diterima')
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Uang yang sudah diterima dari klien')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state)),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                        DatePicker::make('closing_date')
                            ->date()
                            ->label('Closing Date (Otomatis dari Pembayaran Pertama)')
                            ->readOnly()
                            ->default(function (Get $get, ?Order $record): string {
                                if ($record && $record->exists) {
                                    $firstPayment = $record->dataPembayaran()->orderBy('tgl_bayar', 'asc')->first();
                                    if ($firstPayment && $firstPayment->tgl_bayar) {
                                        return Carbon::parse($firstPayment->tgl_bayar)->format('Y-m-d');
                                    }
                                }
                                $paymentItems = $get('Jika Ada Pembayaran') ?? [];
                                if (! empty($paymentItems)) {
                                    usort($paymentItems, function ($a, $b) {
                                        return strtotime($a['tgl_bayar'] ?? 'now') <=> strtotime($b['tgl_bayar'] ?? 'now');
                                    });
                                    if (isset($paymentItems[0]['tgl_bayar']) && ! empty($paymentItems[0]['tgl_bayar'])) {
                                        return Carbon::parse($paymentItems[0]['tgl_bayar'])->format('Y-m-d');
                                    }
                                }

                                return now()->format('Y-m-d');
                            })
                            ->columnSpanFull(),
                        Toggle::make('is_paid')
                            ->label('Lunas / Belum')
                            ->default(false)
                            ->disabled()
                            ->dehydrated()
                            ->onIcon('heroicon-m-bolt')
                            ->offIcon('heroicon-m-user')
                            ->helperText('Otomatis lunas jika sisa pembayaran ≤ 0'),
                    ]),
                Step::make('Pengeluaran')
                    ->icon('heroicon-o-book-open')
                    ->description('Catat detail pengeluaran')
                    ->schema([
                        Section::make('Pengeluaran')
                            ->description('Catat pengeluaran ke vendor. Setiap vendor hanya boleh dipilih satu kali per order.')
                            ->schema([
                                TextEntry::make('expenses_summary')
                                    ->label('Ringkasan')
                                    ->state(function (?Order $record): string {
                                        if (! $record) {
                                            return '-';
                                        }

                                        $count = $record->expenses()->count();
                                        $sum = (int) $record->expenses()->sum('amount');

                                        return "Total pengeluaran: {$count} item | Total nominal: Rp ".number_format($sum, 0, '.', ',');
                                    }),
                                TextEntry::make('expenses_manage')
                                    ->label('Kelola Pengeluaran')
                                    ->state('Gunakan tab Pengeluaran di bawah form untuk tambah/edit pengeluaran.'),
                            ])->columnSpanFull(),
                    ]),
                Step::make('Riwayat Modifikasi')
                    ->icon('heroicon-o-clock')
                    ->description('Catat detail modifikasi')
                    ->schema([
                        TextInput::make('created_at_display')
                            ->label('Dibuat')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, ?Order $record): void {
                                $component->state($record?->created_at?->diffForHumans());
                            }),
                        TextInput::make('updated_at_display')
                            ->label('Terakhir Diubah')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, ?Order $record): void {
                                $component->state($record?->updated_at?->diffForHumans());
                            }),
                        TextInput::make('last_edited_by_display')
                            ->label('Terakhir Diedit Oleh')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, ?Order $record): void {
                                if ($record?->lastEditedBy) {
                                    $component->state($record->lastEditedBy->name.' pada '.$record->updated_at?->format('d M Y H:i'));
                                } else {
                                    $component->state('Belum dilacak');
                                }
                            }),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Order $record) => $record === null),
            ])
                ->columnSpan('full')
                ->columns(3)
                ->skippable(),
        ]);
    }
}

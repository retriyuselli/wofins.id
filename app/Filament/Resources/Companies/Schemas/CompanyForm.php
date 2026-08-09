<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Models\User;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        $isSuperAdmin = ProFeatures::actorIsSuperAdmin();

        return $schema
            ->components([
                Tabs::make('Company')
                    ->tabs([
                        Tabs\Tab::make('Informasi Perusahaan')
                            ->icon(Heroicon::OutlinedPencilSquare)
                            ->schema([
                                Section::make('Informasi Perusahaan')
                                    ->schema([
                                        Grid::make()
                                            ->columns(3)
                                            ->schema([
                                                TextInput::make('company_name')
                                                    ->required()
                                                    ->minLength(3)
                                                    ->maxLength(255)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('Nama perusahaan'),
                                                TextInput::make('business_license')
                                                    ->maxLength(255)
                                                    ->placeholder('Nomor izin usaha (opsional)')
                                                    ->helperText('Boleh dilengkapi kemudian'),
                                                TextInput::make('owner_name')
                                                    ->required()
                                                    ->minLength(3)
                                                    ->maxLength(255)
                                                    ->placeholder('Nama pemilik'),
                                                TextInput::make('jabatan_owner')
                                                    ->maxLength(255)
                                                    ->placeholder('Jabatan pemilik'),
                                                TextInput::make('inisial_wo')
                                                    ->label('Inisial WO')
                                                    ->maxLength(50)
                                                    ->placeholder('MW'),
                                                TextInput::make('inisial_kontak')
                                                    ->label('Inisial Kontrak')
                                                    ->maxLength(50)
                                                    ->placeholder('KKP'),
                                                TextInput::make('legal_entity_type')
                                                    ->maxLength(100)
                                                    ->placeholder('PT, CV, Firma'),
                                                TextInput::make('established_year')
                                                    ->numeric()
                                                    ->minValue(1900)
                                                    ->maxValue((int) date('Y'))
                                                    ->placeholder('Tahun berdiri'),
                                                TextInput::make('employee_count')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(100000)
                                                    ->formatStateUsing(fn ($state) => CompanySubscription::seatsUsed())
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->placeholder('Jumlah karyawan'),
                                            ]),
                                    ]),
                                Section::make('Paket Langganan')
                                    ->description($isSuperAdmin
                                        ? 'Bukan role Spatie — menentukan kuota dan fitur yang aktif.'
                                        : 'Paket diatur saat Approve / oleh admin platform. Hubungi support untuk upgrade.')
                                    ->schema([
                                        Grid::make()
                                            ->columns(2)
                                            ->schema([
                                                Select::make('subscription_plan')
                                                    ->label('Paket')
                                                    ->options(\App\Support\PricingPlans::companyPlanOptions())
                                                    ->placeholder('Pilih paket')
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin)
                                                    ->helperText(fn () => $isSuperAdmin
                                                        ? ('1 WO = 1 Company. '.CompanySubscription::quotasOverview()
                                                            .' · Kursi user tidak menghitung akun super_admin.')
                                                        : ('Paket aktif: '.CompanySubscription::planLabel()
                                                            .' · '.CompanySubscription::seatSummary()))
                                                    ->columnSpanFull(),
                                                TextInput::make('seat_limit_override')
                                                    ->label('Override kuota pengguna')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('vendor_limit_override')
                                                    ->label('Override kuota vendor')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('product_limit_override')
                                                    ->label('Override kuota produk')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('order_limit_override')
                                                    ->label('Override kuota proyek wedding')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('prospect_limit_override')
                                                    ->label('Override kuota prospek')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('simulasi_limit_override')
                                                    ->label('Override kuota simulasi')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('payment_method_limit_override')
                                                    ->label('Override kuota rekening')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('fixed_asset_limit_override')
                                                    ->label('Override kuota aset tetap')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('piutang_limit_override')
                                                    ->label('Override kuota piutang')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('pembayaran_piutang_limit_override')
                                                    ->label('Override kuota bayar piutang')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('data_pembayaran_limit_override')
                                                    ->label('Override kuota pendapatan wedding')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('expense_limit_override')
                                                    ->label('Override kuota pengeluaran wedding')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('expense_ops_limit_override')
                                                    ->label('Override kuota pengeluaran ops')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('pendapatan_lain_limit_override')
                                                    ->label('Override kuota pendapatan lain')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                TextInput::make('pengeluaran_lain_limit_override')
                                                    ->label('Override kuota pengeluaran lain')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Kosongkan = ikut paket')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                                DatePicker::make('subscription_expires_at')
                                                    ->label('Berlaku sampai')
                                                    ->displayFormat('d M Y')
                                                    ->native(false)
                                                    ->helperText('Opsional — untuk pengingat masa aktif.')
                                                    ->visible($isSuperAdmin)
                                                    ->disabled(! $isSuperAdmin)
                                                    ->dehydrated($isSuperAdmin),
                                            ]),
                                    ]),
                                Section::make('Informasi Rekening')
                                    ->schema([
                                        Select::make('payment_method_id')
                                            ->label('Rekening Bank Utama')
                                            ->relationship(
                                                'paymentMethod',
                                                'bank_name',
                                                fn ($query) => \App\Support\UserVisibility::constrainCompanyQuery($query)
                                            )
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->bank_name} - {$record->no_rekening} ({$record->name})")
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Pilih rekening bank utama (milik perusahaan Anda)'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Kontak & Alamat')
                            ->icon(Heroicon::OutlinedCurrencyDollar)
                            ->schema([
                                Section::make('Kontak & Alamat')
                                    ->schema([
                                        Grid::make()
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('email')
                                                    ->label('Email address')
                                                    ->email()
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(255)
                                                    ->placeholder('contoh@perusahaan.com')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->helperText('Email dari pendaftaran — tidak dapat diubah.'),
                                                TextInput::make('phone')
                                                    ->tel()
                                                    ->required()
                                                    ->minLength(8)
                                                    ->maxLength(20)
                                                    ->regex('/^[0-9+\s\-]+$/')
                                                    ->placeholder('+62 812 xxxx xxxx'),
                                            ]),
                                        Textarea::make('address')
                                            ->maxLength(1000)
                                            ->columnSpanFull()
                                            ->placeholder('Alamat lengkap perusahaan'),
                                        Grid::make()
                                            ->columns(3)
                                            ->schema([
                                                TextInput::make('city')
                                                    ->maxLength(100)
                                                    ->placeholder('Kota/Kabupaten'),
                                                TextInput::make('province')
                                                    ->maxLength(100)
                                                    ->placeholder('Provinsi'),
                                                TextInput::make('postal_code')
                                                    ->minLength(4)
                                                    ->maxLength(10)
                                                    ->regex('/^[0-9]+$/')
                                                    ->placeholder('Kode pos'),
                                            ]),
                                        Grid::make()
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('website')
                                                    ->url()
                                                    ->maxLength(255)
                                                    ->placeholder('https://example.com')
                                                    ->helperText('Gunakan URL lengkap diawali http:// atau https://'),
                                                FileUpload::make('logo_url')
                                                    ->disk('public')
                                                    ->directory('company/logo')
                                                    ->image()
                                                    ->maxSize(5120),
                                            ]),
                                        Grid::make()
                                            ->columns(2)
                                            ->schema([
                                                FileUpload::make('favicon_url')
                                                    ->disk('public')
                                                    ->directory('company/favicon')
                                                    ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/svg+xml'])
                                                    ->imagePreviewHeight('64')
                                                    ->maxSize(5120),
                                                FileUpload::make('image_login')
                                                    ->label('Image Login')
                                                    ->disk('public')
                                                    ->directory('company/login_image')
                                                    ->image()
                                                    ->maxSize(5120)
                                                    ->helperText('Gambar untuk halaman login frontend'),
                                                TextInput::make('logo_url')
                                                    ->hidden(),
                                            ]),
                                        Textarea::make('description')
                                            ->maxLength(1000)
                                            ->columnSpanFull()
                                            ->placeholder('Deskripsi singkat perusahaan'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Legal Perusahaan')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema([
                                Section::make('Legal Perusahaan')
                                    ->schema([
                                        Grid::make()
                                            ->columns(3)
                                            ->schema([
                                                TextInput::make('deed_of_establishment')
                                                    ->maxLength(255)
                                                    ->placeholder('Nomor akta pendirian'),
                                                DatePicker::make('deed_date'),
                                                TextInput::make('notary_name')
                                                    ->maxLength(255)
                                                    ->placeholder('Nama notaris'),
                                                TextInput::make('notary_license_number')
                                                    ->maxLength(100)
                                                    ->placeholder('Nomor izin notaris'),
                                                TextInput::make('nib_number')
                                                    ->maxLength(50)
                                                    ->placeholder('Nomor NIB'),
                                                Grid::make()
                                                    ->columns(2)
                                                    ->schema([
                                                        DatePicker::make('nib_issued_date'),
                                                        DatePicker::make('nib_valid_until'),
                                                    ]),
                                                TextInput::make('npwp_number')
                                                    ->maxLength(20)
                                                    ->regex('/^[0-9\.\-]+$/')
                                                    ->helperText('NPWP 15 digit, boleh berisi tanda titik atau minus'),
                                                DatePicker::make('npwp_issued_date'),
                                                TextInput::make('tax_office')
                                                    ->maxLength(255)
                                                    ->placeholder('Kantor pajak'),
                                            ]),
                                    ]),
                            ]),
                        Tabs\Tab::make('Dokumen')
                            ->icon(Heroicon::OutlinedIdentification)
                            ->schema([
                                Section::make('Dokumen')
                                    ->schema([
                                        Grid::make()
                                            ->columns(2)
                                            ->schema([
                                                FileUpload::make('legal_documents')
                                                    ->disk('public')
                                                    ->directory('company/legal')
                                                    ->acceptedFileTypes(['application/pdf'])
                                                    ->maxSize(5120)
                                                    ->helperText('Dokumen legal perusahaan (PDF)')
                                                    ->openable()
                                                    ->multiple()
                                                    ->dehydrateStateUsing(fn ($state) => $state ? (is_array($state) ? $state : [$state]) : []),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}

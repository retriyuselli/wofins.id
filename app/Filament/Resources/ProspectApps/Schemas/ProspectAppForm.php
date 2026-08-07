<?php

namespace App\Filament\Resources\ProspectApps\Schemas;

use App\Enums\ProspectAppStatus;
use App\Models\ProspectApp;
use App\Models\User;
use App\Support\PricingPlans;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class ProspectAppForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kontak')
                    ->description('Detail kontak calon pelanggan')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Select::make('user_id')
                            ->label('Akun User')
                            ->relationship(
                            'user',
                            'email',
                            fn (\Illuminate\Database\Eloquent\Builder $query) => \App\Support\UserVisibility::constrainUsersQuery($query)
                        )
                            ->getOptionLabelFromRecordUsing(
                                fn (User $record): string => "{$record->name} ({$record->email})"
                            )
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->nullable()
                            ->helperText('Opsional — tautkan ke akun login. Aktivasi role tetap lewat Users → Approve.')
                            ->columnSpanFull(),

                        TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('contoh: John Doe')
                            ->autofocus(),

                        TextInput::make('email')
                            ->label('Alamat Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('contoh: john.doe@example.com'),

                        TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->placeholder('contoh: 081234567890'),

                        TextInput::make('position')
                            ->label('Posisi Pekerjaan')
                            ->maxLength(255)
                            ->placeholder('contoh: Manajer Marketing'),
                    ])
                    ->columns(2),

                Section::make('Informasi Perusahaan')
                    ->description('Data perusahaan calon (tersimpan di prospect_apps, bukan Company sistem)')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Nama Perusahaan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('contoh: Acme Corp'),

                        Select::make('industry_id')
                            ->label('Departemen')
                            ->relationship('industry', 'industry_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih departemen'),

                        TextInput::make('name_of_website')
                            ->label('Website/Domain')
                            ->maxLength(255)
                            ->placeholder('contoh: www.example.com'),

                        Select::make('user_size')
                            ->label('Jumlah Karyawan')
                            ->options(fn (Get $get): array => ProspectApp::userSizeOptions($get('user_size')))
                            ->required()
                            ->placeholder('Pilih jumlah karyawan'),
                    ])
                    ->columns(2),

                Section::make('Detail Pendaftaran')
                    ->description('Minat paket & status lead — tidak mengubah paket langganan Company')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Textarea::make('reason_for_interest')
                            ->label('Kebutuhan & Tantangan Bisnis')
                            ->rows(3)
                            ->required()
                            ->maxLength(1000)
                            ->placeholder('Jelaskan kebutuhan atau tantangan bisnis calon'),

                        Select::make('status')
                            ->label('Status Aplikasi')
                            ->options(ProspectAppStatus::class)
                            ->default(ProspectAppStatus::Pending)
                            ->required()
                            ->helperText('Status lead saja. Menandai Disetujui di sini tidak mengaktifkan akun — gunakan Users → Approve.'),

                        Select::make('service')
                            ->label('Minat Paket Layanan')
                            ->options(fn (Get $get): array => PricingPlans::filamentOptions($get('service')))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set) {
                                $amount = PricingPlans::annualAmount($state);
                                $set('harga', $amount);
                                $set('sisa_bayar', $amount);
                                $set('bayar', null);
                            })
                            ->helperText(fn (Get $get): string => $get('service') === 'lain_lain'
                                ? 'Custom — isi anggaran manual. Ini minat sales, bukan paket aktif di Company.'
                                : 'Sama dengan halaman Harga. Minat calon saja — paket aktif diatur di Admin → Company.'),
                    ])
                    ->columns(1),

                Section::make('Pembayaran & Catatan')
                    ->schema([
                        TextInput::make('harga')
                            ->label('Anggaran')
                            ->prefix('Rp. ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                            ->readOnly(fn (Get $get): bool => $get('service') !== 'lain_lain'
                                && PricingPlans::annualAmount($get('service')) !== null)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $get, Set $set) {
                                if ($get('service') === 'lain_lain'
                                    || PricingPlans::annualAmount($get('service')) === null) {
                                    $harga = (int) preg_replace('/[^\d]/', '', (string) $state);
                                    $bayar = (int) preg_replace('/[^\d]/', '', (string) $get('bayar'));
                                    $set('sisa_bayar', max(0, $harga - $bayar));
                                }
                            })
                            ->helperText(fn (Get $get): string => $get('service') === 'lain_lain'
                                || PricingPlans::annualAmount($get('service')) === null
                                ? 'Masukkan anggaran secara manual'
                                : 'Anggaran tahunan otomatis dari paket Harga'),

                        DatePicker::make('tgl_bayar')
                            ->label('Tanggal Pembayaran')
                            ->displayFormat('d M Y')
                            ->helperText('Jika ada pembayaran, isi tanggalnya'),

                        TextInput::make('bayar')
                            ->label('Jumlah Dibayar')
                            ->prefix('Rp. ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                            ->helperText('Jika ada pembayaran, isi nominalnya')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $service = $get('service');
                                $harga = PricingPlans::annualAmount($service)
                                    ?? (int) preg_replace('/[^\d]/', '', (string) $get('harga'));
                                $bayar = (int) preg_replace('/[^\d]/', '', (string) $state);

                                $set('sisa_bayar', max(0, $harga - $bayar));
                            }),

                        TextInput::make('sisa_bayar')
                            ->label('Sisa Pembayaran')
                            ->prefix('Rp. ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->dehydrated(false)
                            ->readOnly()
                            ->helperText('Otomatis: Anggaran - Jumlah Dibayar'),

                        RichEditor::make('notes')
                            ->label('Catatan Internal')
                            ->placeholder('Tambahkan catatan internal atau komentar')
                            ->columnSpanFull(),

                        DateTimePicker::make('submitted_at')
                            ->label('Tanggal & Waktu Pengajuan')
                            ->default(now())
                            ->displayFormat('d M Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }
}

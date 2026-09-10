<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use App\Support\ProFeatures;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Perusahaan')
                    ->description('Rekening hanya terlihat oleh pengguna dalam company yang sama.')
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->relationship('company', 'company_name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Pilih company tenant. Kosong = katalog platform (hanya super admin).'),
                    ])
                    ->visible(fn () => ProFeatures::actorIsSuperAdmin()),
                Section::make('Detail Rekening')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('nama pemilik rekening')
                            ->maxLength(255),
                        TextInput::make('bank_name')
                            ->prefix('Bank ')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('cabang')
                            ->placeholder('cabang bank (opsional)')
                            ->maxLength(255),
                        TextInput::make('no_rekening')
                            ->required()
                            ->numeric(),
                        Toggle::make('is_cash')
                            ->required(),
                    ])->columns(2),
                Section::make('Saldo Awal')
                    ->description('Isi hanya jika rekening ini sudah punya saldo sebelum dicatat di WOFINS. Jika saldo awal 0, semua pembayaran (termasuk yang tanggalnya sebelum hari ini) masuk ke saldo rekening.')
                    ->schema([
                        TextInput::make('opening_balance')
                            ->label('Saldo Awal (Opening Balance)')
                            ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                            ->prefix('Rp')
                            ->required()
                            ->default(0)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(','),
                        DatePicker::make('opening_balance_date')
                            ->label('Tanggal Saldo Awal')
                            ->helperText('Wajib diisi jika saldo awal > 0. Kosongkan jika rekening mulai dari nol agar semua transaksi dihitung.')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->required(fn (Get $get): bool => (int) preg_replace('/[^\d]/', '', (string) $get('opening_balance')) > 0),
                    ])->columns(2),
            ]);
    }
}

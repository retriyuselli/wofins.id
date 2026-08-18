<?php

namespace App\Filament\Resources\Payrolls\Schemas;

use App\Models\Payroll;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class PayrollForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Payroll')
                    ->tabs([
                        Tab::make('Karyawan & Periode')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Informasi Karyawan')
                                    ->description('Pilih karyawan dan periode payroll')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('employee_id')
                                                    ->label('Karyawan')
                                                    ->relationship('employee', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                        if (! $state) {
                                                            return;
                                                        }
                                                        $employee = \App\Models\Employee::query()->find($state);
                                                        if (! $employee) {
                                                            return;
                                                        }
                                                        $baseGaji = (int) ($employee->salary ?? 0);
                                                        $baseTunjangan = (int) ($employee->tunjangan ?? 0);
                                                        $set('user_id', $employee->user_id);
                                                        $set('gaji_pokok', (string) $baseGaji);
                                                        $set('tunjangan', (string) $baseTunjangan);
                                                        $monthlySalary = Payroll::computeMonthly(
                                                            $baseGaji,
                                                            $baseTunjangan,
                                                            (int) $get('bonus'),
                                                            (int) $get('pengurangan'),
                                                        );
                                                        $set('monthly_salary', (string) $monthlySalary);
                                                        $set('annual_salary', (string) Payroll::computeAnnualBase($baseGaji, $baseTunjangan));
                                                        $set('total_compensation', (string) Payroll::computeTotalCompensationBase($baseGaji, $baseTunjangan, (int) $get('pengurangan')));
                                                    })
                                                    ->helperText('Pilih dari master Employee (boleh tanpa akun login).')
                                                    ->columnSpan(2),

                                                Group::make([
                                                    Select::make('period_month')
                                                        ->label('Bulan Periode')
                                                        ->options([
                                                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                                                        ])
                                                        ->default(now()->month)
                                                        ->required()
                                                        ->live()
                                                        ->helperText('Pilih bulan periode payroll'),

                                                    Select::make('period_year')
                                                        ->label('Tahun Periode')
                                                        ->options(function () {
                                                            $currentYear = now()->year;
                                                            $years = [];
                                                            for ($year = $currentYear - 1; $year <= $currentYear + 1; $year++) {
                                                                $years[$year] = $year;
                                                            }

                                                            return $years;
                                                        })
                                                        ->default(now()->year)
                                                        ->required()
                                                        ->live()
                                                        ->helperText('Pilih tahun periode payroll'),
                                                ])
                                                    ->columnSpan(1),
                                            ]),

                                        Placeholder::make('employee_info')
                                            ->label('Info Karyawan')
                                            ->content(function (Get $get): string {
                                                $employeeId = $get('employee_id');
                                                if (! $employeeId) {
                                                    return 'Pilih karyawan untuk melihat informasi';
                                                }

                                                $employee = \App\Models\Employee::query()->with('user.status')->find($employeeId);
                                                if (! $employee) {
                                                    return 'Karyawan tidak ditemukan';
                                                }

                                                $login = $employee->user_id ? "Login: {$employee->user?->email}" : 'Tanpa akun login';
                                                $join = $employee->date_of_join?->format('d/m/Y') ?? '-';

                                                return "{$employee->name} · Bergabung {$join} · {$login}";
                                            }),
                                    ])->columns(1),
                            ]),

                        Tab::make('Gaji')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Informasi Gaji')
                                    ->description('Gaji pokok & tunjangan dari master Karyawan (read-only). Ubah hanya pengurangan & bonus untuk periode ini.')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('gaji_pokok')
                                                    ->label('Gaji Pokok')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-currency-dollar')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace(',', '', (string) $state))
                                                    ->helperText('Dari master Karyawan — ubah di menu Karyawan.')
                                                    ->extraAttributes(['class' => 'bg-gray-50 text-right']),

                                                TextInput::make('tunjangan')
                                                    ->label('Tunjangan')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-plus')
                                                    ->default(0)
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace(',', '', (string) $state))
                                                    ->helperText('Dari master Karyawan — ubah di menu Karyawan.')
                                                    ->extraAttributes(['class' => 'bg-gray-50 text-right']),

                                                TextInput::make('pengurangan')
                                                    ->label('Pengurangan')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-minus')
                                                    ->placeholder('BPJS, potongan, dll.')
                                                    ->default(0)
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->extraAttributes(['class' => 'bg-amber-50 text-right'])
                                                    ->live(onBlur: true)
                                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace(',', '', (string) $state))
                                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                                        self::recalculatePayrollTotals($get, $set);
                                                    })
                                                    ->helperText('Boleh diubah per periode'),

                                                TextInput::make('bonus')
                                                    ->label('Bonus')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-gift')
                                                    ->placeholder('0')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->default(0)
                                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace(',', '', (string) $state))
                                                    ->live(onBlur: true)
                                                    ->extraAttributes(['class' => 'bg-emerald-50 text-right'])
                                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                                        self::recalculatePayrollTotals($get, $set);
                                                    })
                                                    ->helperText('Boleh diubah per periode'),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('monthly_salary')
                                                    ->label('Total Gaji Bulanan')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-calculator')
                                                    ->readOnly()
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->formatStateUsing(fn ($state) => $state === null ? null : number_format((int) str_replace(',', '', (string) $state), 0, '.', ','))
                                                    ->dehydrated(false)
                                                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                                        if ($record) {
                                                            $monthly = Payroll::computeMonthly(
                                                                $record->gaji_pokok ?? 0,
                                                                $record->tunjangan ?? 0,
                                                                $record->bonus ?? 0,
                                                                $record->pengurangan ?? 0,
                                                            );
                                                            $component->state((string) (int) $monthly);
                                                        }
                                                    })
                                                    ->helperText('Otomatis: (Gaji Pokok + Tunjangan + Bonus) - Pengurangan')
                                                    ->extraAttributes(['class' => 'bg-blue-50 text-right']),
                                                TextInput::make('annual_salary')
                                                    ->label('Gaji Tahunan')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-calculator')
                                                    ->readOnly()
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->formatStateUsing(fn ($state) => $state === null ? null : number_format((int) str_replace(',', '', (string) $state), 0, '.', ','))
                                                    ->dehydrated(false)
                                                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                                        if ($record) {
                                                            $component->state((string) (int) Payroll::computeAnnualBase($record->gaji_pokok ?? 0, $record->tunjangan ?? 0));
                                                        }
                                                    })
                                                    ->helperText('Otomatis: (Gaji Pokok + Tunjangan) × 12')
                                                    ->extraAttributes(['class' => 'bg-gray-50 text-right']),
                                                TextInput::make('total_compensation')
                                                    ->label('Total Kompensasi')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-calculator')
                                                    ->readOnly()
                                                    ->dehydrated(false)
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->formatStateUsing(fn ($state) => $state === null ? null : number_format((int) str_replace(',', '', (string) $state), 0, '.', ','))
                                                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                                        if ($record) {
                                                            $component->state((string) (int) Payroll::computeTotalCompensationBase($record->gaji_pokok ?? 0, $record->tunjangan ?? 0, $record->pengurangan ?? 0));
                                                        }
                                                    })
                                                    ->helperText('Total: Gaji Tahunan (tanpa bonus terpisah)')
                                                    ->extraAttributes(['class' => 'bg-gray-50 text-right']),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Review')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Section::make('Informasi Review')
                                    ->description('Jadwal review gaji dan performa')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                DatePicker::make('last_review_date')
                                                    ->label('Tanggal Review Terakhir')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ->helperText('Kapan terakhir kali direview'),

                                                DatePicker::make('next_review_date')
                                                    ->label('Tanggal Review Berikutnya')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ->helperText('Jadwal review berikutnya')
                                                    ->afterOrEqual('today'),
                                            ]),

                                        Textarea::make('notes')
                                            ->label('Catatan')
                                            ->placeholder('Catatan tambahan mengenai payroll ini...')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->helperText('Catatan internal (maksimal 1000 karakter)'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function resolveMonth(mixed $value): int
    {
        if ($value instanceof \Carbon\CarbonInterface) {
            return (int) $value->month;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }

    protected static function resolveYear(mixed $value): int
    {
        if ($value instanceof \Carbon\CarbonInterface) {
            return (int) $value->year;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }

    protected static function recalculatePayrollTotals(Get $get, Set $set): void
    {
        $monthlySalary = Payroll::computeMonthly(
            $get('gaji_pokok'),
            $get('tunjangan'),
            $get('bonus'),
            $get('pengurangan'),
        );

        $set('monthly_salary', (string) $monthlySalary);
        $set('annual_salary', (string) Payroll::computeAnnualBase($get('gaji_pokok'), $get('tunjangan')));
        $set('total_compensation', (string) Payroll::computeTotalCompensationBase($get('gaji_pokok'), $get('tunjangan'), $get('pengurangan')));
    }
}

<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Informasi Karyawan')
                    ->tabs([
                        Tab::make('Informasi Dasar')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Detail Personal')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->placeholder('Nama lengkap (depan dan belakang)')
                                                    ->maxLength(255)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, Set $set, ?Employee $record) {
                                                        $set('slug', Employee::generateUniqueSlug(
                                                            (string) $state,
                                                            $record?->id
                                                        ));

                                                        $matches = Employee::findSameNameInCompany(
                                                            (string) $state,
                                                            $record?->id
                                                        );

                                                        if ($matches->isEmpty()) {
                                                            return;
                                                        }

                                                        $list = $matches
                                                            ->map(fn (Employee $e) => $e->name.($e->email ? " ({$e->email})" : ''))
                                                            ->implode(', ');

                                                        Notification::make()
                                                            ->title('Nama sudah dipakai')
                                                            ->body("Ada karyawan dengan nama sama: {$list}. Anda tetap boleh menyimpan — pastikan ini memang orang berbeda.")
                                                            ->warning()
                                                            ->persistent()
                                                            ->send();
                                                    })
                                                    ->helperText(function (Get $get, ?Employee $record): string {
                                                        $matches = Employee::findSameNameInCompany(
                                                            (string) $get('name'),
                                                            $record?->id
                                                        );

                                                        if ($matches->isEmpty()) {
                                                            return 'Nama boleh sama. Identitas unik memakai email.';
                                                        }

                                                        $list = $matches
                                                            ->take(3)
                                                            ->map(fn (Employee $e) => $e->name.($e->email ? " ({$e->email})" : ''))
                                                            ->implode(', ');

                                                        return "Peringatan: nama sama sudah ada — {$list}. Tetap boleh disimpan.";
                                                    }),

                                                TextInput::make('slug')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->maxLength(255)
                                                    ->helperText('Otomatis unik (nama sama → slug-1, slug-2, dst).'),

                                                DatePicker::make('date_of_birth')
                                                    ->label('Tanggal Lahir')
                                                    ->required()
                                                    ->maxDate(now()->subYears(18))
                                                    ->displayFormat('d M Y'),

                                                FileUpload::make('photo')
                                                    ->label('Foto Profil')
                                                    ->image()
                                                    ->openable()
                                                    ->downloadable()
                                                    ->directory('employee-photos'),
                                            ]),
                                    ]),

                                Section::make('Informasi Kontak')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('email')
                                                    ->email()
                                                    ->required()
                                                    ->unique(
                                                        table: Employee::class,
                                                        column: 'email',
                                                        ignoreRecord: true,
                                                        modifyRuleUsing: fn ($rule) => $rule->where(
                                                            'company_id',
                                                            \App\Support\UserVisibility::companyId()
                                                        )
                                                    )
                                                    ->validationMessages([
                                                        'unique' => 'Email ini sudah dipakai karyawan lain di company Anda.',
                                                    ])
                                                    ->helperText('Wajib unik per company — dipakai sebagai identitas karyawan.')
                                                    ->maxLength(255),

                                                TextInput::make('phone')
                                                    ->tel()
                                                    ->required()
                                                    ->maxLength(20)
                                                    ->prefix('+62')
                                                    ->telRegex('/^[0-9]{9,15}$/')
                                                    ->placeholder('8xxxxxxxxx'),

                                                TextInput::make('instagram')
                                                    ->prefix('@')
                                                    ->maxLength(255),

                                                Textarea::make('address')
                                                    ->required()
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Detail Kepegawaian')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Section::make('Posisi & Peran')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('position')
                                                    ->required()
                                                    ->options([
                                                        'Account Manager' => 'Account Manager',
                                                        'Event Manager' => 'Event Manager',
                                                        'Crew Internal' => 'Crew Internal',
                                                        'Finance' => 'Finance',
                                                        'Founder' => 'Founder',
                                                        'Co Founder' => 'Co Founder',
                                                        'Direktur' => 'Direktur',
                                                        'Wakil Direktur' => 'Wakil Direktur',
                                                        'Other' => 'Other',
                                                    ])
                                                    ->searchable(),

                                                Select::make('user_id')
                                                    ->relationship(
                                                        'user',
                                                        'name',
                                                        fn (\Illuminate\Database\Eloquent\Builder $query) => \App\Support\UserVisibility::constrainUsersQuery($query)
                                                            ->where(function ($q) {
                                                                $q->whereNull('status')->orWhere('status', 'active');
                                                            })
                                                    )
                                                    ->label('Akun Pengguna Terkait')
                                                    ->helperText('Opsional. Hanya User terdaftar (seat) di company ini — untuk portal ESS. Kosongkan jika karyawan tanpa login.')
                                                    ->preload()
                                                    ->searchable()
                                                    ->nullable()
                                                    ->unique(ignoreRecord: true),

                                                DatePicker::make('date_of_join')
                                                    ->label('Tanggal Bergabung')
                                                    ->required()
                                                    ->displayFormat('d M Y')
                                                    ->default(now()),

                                                DatePicker::make('date_of_out')
                                                    ->label('Tanggal Berhenti')
                                                    ->displayFormat('d M Y')
                                                    ->minDate(fn (Get $get) => $get('date_of_join')),
                                            ]),
                                    ]),

                                Section::make('Kompensasi & Perbankan')
                                    ->description('Sumber gaji master: gaji pokok & tunjangan di sini. Saat generate payroll nilai di-snapshot; di periode hanya ubah pengurangan & bonus.')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('salary')
                                                    ->label('Gaji Pokok')
                                                    ->required()
                                                    ->prefix('Rp. ')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                                    ->placeholder('0')
                                                    ->helperText('Wajib diisi — dipakai otomatis saat Generate Periode.'),

                                                TextInput::make('tunjangan')
                                                    ->label('Tunjangan')
                                                    ->prefix('Rp. ')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->default(0)
                                                    ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                                    ->placeholder('0')
                                                    ->helperText('Tunjangan tetap bulanan (snapshot ke payroll saat generate).'),

                                                TextInput::make('bank_name')
                                                    ->required()
                                                    ->maxLength(255),

                                                TextInput::make('no_rek')
                                                    ->label('Nomor Rekening')
                                                    ->required()
                                                    ->numeric()
                                                    ->minLength(10)
                                                    ->maxLength(20),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Dokumen & Catatan')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Grid::make(1)
                                    ->schema([
                                        FileUpload::make('kontrak')
                                            ->label('Kontrak Kerja')
                                            ->directory('employee-contracts')
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->openable()
                                            ->downloadable(),

                                        Textarea::make('note')
                                            ->label('Additional Notes')
                                            ->placeholder('Add any special considerations or notes about this employee')
                                            ->rows(3),

                                        TextInput::make('created_at_display')
                                            ->label('Dibuat')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->afterStateHydrated(function ($component, $state, ?Employee $record): void {
                                                $component->state($record?->created_at?->diffForHumans());
                                            })
                                            ->hidden(fn (?Employee $record) => $record === null),

                                        TextInput::make('updated_at_display')
                                            ->label('Diperbarui')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->afterStateHydrated(function ($component, $state, ?Employee $record): void {
                                                $component->state($record?->updated_at?->diffForHumans());
                                            })
                                            ->hidden(fn (?Employee $record) => $record === null),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}

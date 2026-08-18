<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use App\Services\PayrollGenerateService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generatePeriod')
                ->label('Generate Periode')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->visible(fn (): bool => Auth::user()?->can('Create:Payroll') ?? false)
                ->form([
                    Select::make('period_month')
                        ->label('Bulan')
                        ->options([
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                        ])
                        ->default(now()->month)
                        ->required()
                        ->native(false),
                    Select::make('period_year')
                        ->label('Tahun')
                        ->options(function (): array {
                            $year = now()->year;
                            $options = [];
                            for ($y = $year + 1; $y >= $year - 5; $y--) {
                                $options[$y] = (string) $y;
                            }

                            return $options;
                        })
                        ->default(now()->year)
                        ->required()
                        ->native(false),
                ])
                ->modalHeading('Generate Payroll Periode')
                ->modalDescription('Membuat draft dari karyawan aktif. Gaji pokok & tunjangan diambil dari master Karyawan; di payroll hanya isi pengurangan & bonus. Yang sudah ada di periode ini, atau belum punya gaji pokok, dilewati.')
                ->modalSubmitActionLabel('Generate')
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $result = app(PayrollGenerateService::class)->generateForPeriod(
                        (int) $data['period_month'],
                        (int) $data['period_year'],
                    );

                    if ($result['created'] === 0 && $result['skipped'] === 0 && ($result['skipped_no_salary'] ?? 0) === 0) {
                        Notification::make()
                            ->title('Tidak ada karyawan aktif')
                            ->body('Tambah data Employee aktif dulu, lalu generate lagi.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $body = "Dibuat: {$result['created']} · Sudah ada: {$result['skipped']}";
                    if (($result['skipped_no_salary'] ?? 0) > 0) {
                        $body .= " · Tanpa gaji pokok: {$result['skipped_no_salary']} (lengkapi di Karyawan)";
                    }

                    Notification::make()
                        ->title('Generate selesai — '.$result['period_label'])
                        ->body($body)
                        ->success()
                        ->send();

                    $this->resetTable();
                }),
            CreateAction::make()
                ->label('Tambah Manual'),
        ];
    }
}

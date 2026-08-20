<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\Payroll;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return UserVisibility::stampCompanyId($data, 'user_id');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $existing = Payroll::where('employee_id', $data['employee_id'])
            ->where('period_month', $data['period_month'])
            ->where('period_year', $data['period_year'])
            ->first();

        if ($existing) {
            Notification::make()
                ->warning()
                ->title('Payroll Sudah Ada')
                ->body('Payroll untuk karyawan dan periode tersebut sudah ada. Mengalihkan ke halaman edit.')
                ->send();

            return $existing;
        }

        return Payroll::create($data);
    }
}

<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = UserVisibility::stampCompanyId($data);

        $base = (string) ($data['slug'] ?? $data['name'] ?? 'karyawan');
        $data['slug'] = Employee::generateUniqueSlug($base);

        $matches = Employee::findSameNameInCompany(
            (string) ($data['name'] ?? ''),
            null,
            isset($data['company_id']) ? (int) $data['company_id'] : null,
        );

        if ($matches->isNotEmpty()) {
            $list = $matches
                ->map(fn (Employee $e) => $e->name.($e->email ? " ({$e->email})" : ''))
                ->implode(', ');

            Notification::make()
                ->title('Disimpan dengan nama yang sudah dipakai')
                ->body("Ada karyawan bernama sama: {$list}. Data tetap disimpan. Slug dibuat unik otomatis.")
                ->warning()
                ->send();
        }

        return $data;
    }
}

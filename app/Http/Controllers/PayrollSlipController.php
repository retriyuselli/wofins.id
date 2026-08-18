<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Support\UserVisibility;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PayrollSlipController extends Controller
{
    public function download(Payroll $record)
    {
        $authUser = Auth::user();
        if (! $authUser) {
            abort(401, 'Unauthorized');
        }

        // Employee boleh tanpa akun login; muat tanpa tenant scope agar slip tetap terbaca
        // setelah otorisasi (mis. company_id karyawan belum terisi / beda konteks).
        $employee = $record->employee_id
            ? Employee::withoutGlobalScopes()->find($record->employee_id)
            : null;
        $linkedUser = $record->user ?? $employee?->user;

        if (! $authUser->roles->contains('name', 'super_admin')) {
            $ownEmployee = \App\Support\HrEmployee::forUser($authUser);
            $owns = ($ownEmployee && $employee && (int) $employee->id === (int) $ownEmployee->id)
                || ($linkedUser && (int) $linkedUser->id === (int) $authUser->id)
                || ((int) $record->user_id === (int) $authUser->id);

            if (! $owns) {
                abort(403, 'Forbidden');
            }
        }

        $person = (object) [
            'name' => $employee?->name ?? $linkedUser?->name ?? 'Karyawan',
            'email' => $employee?->email ?? $linkedUser?->email ?? '-',
            'phone' => $employee?->phone ?? $linkedUser?->phone_number ?? '-',
            'department' => $employee?->position ?? $linkedUser?->department ?? null,
        ];

        $periodLabel = $this->periodLabel((int) $record->period_month, (int) $record->period_year);
        $companyData = $this->companyViewData($authUser, $employee, $linkedUser);

        return view('payroll.slip-gaji-download', array_merge([
            'record' => $record,
            'person' => $person,
            // Kompatibilitas template lama yang masih merujuk $user
            'user' => $person,
            'employee' => $employee,
            'periodLabel' => $periodLabel,
        ], $companyData));
    }

    /**
     * Branding slip dari company yang login (fallback: company karyawan).
     *
     * @return array{
     *     company: ?Company,
     *     companyName: string,
     *     companyAddress: ?string,
     *     companyEmail: ?string,
     *     companyPhone: ?string,
     *     companyWebsite: ?string,
     *     companyLogoUrl: string,
     *     companyBrandVersion: int
     * }
     */
    private function companyViewData($authUser, ?Employee $employee, $linkedUser): array
    {
        $companyId = UserVisibility::companyId($authUser)
            ?? ($employee?->company_id ? (int) $employee->company_id : null)
            ?? ($linkedUser?->company_id ? (int) $linkedUser->company_id : null);

        $company = $companyId
            ? Company::query()->find($companyId)
            : null;

        $logoUrl = asset('images/logomki.png');
        if ($company?->logo_url && Storage::disk('public')->exists($company->logo_url)) {
            $logoUrl = asset('storage/'.ltrim($company->logo_url, '/'));
        }

        $addressParts = array_filter([
            $company?->address,
            collect([
                $company?->city,
                $company?->province,
                $company?->postal_code,
            ])->filter()->implode(', '),
        ], fn ($part) => filled($part));

        $brandVersion = 1;
        if ($company?->updated_at) {
            try {
                $brandVersion = (int) $company->updated_at->timestamp;
            } catch (\Throwable) {
                $brandVersion = 1;
            }
        }

        return [
            'company' => $company,
            'companyName' => $company?->company_name ?? config('app.name'),
            'companyAddress' => $addressParts !== [] ? implode("\n", $addressParts) : null,
            'companyEmail' => $company?->email,
            'companyPhone' => $company?->phone,
            'companyWebsite' => $company?->website,
            'companyLogoUrl' => $logoUrl,
            'companyBrandVersion' => $brandVersion,
        ];
    }

    private function periodLabel(int $month, int $year): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return ($months[$month] ?? $month).' '.$year;
    }
}

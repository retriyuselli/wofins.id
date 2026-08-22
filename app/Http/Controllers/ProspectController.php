<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Prospect;
use App\Models\User;
use App\Support\CompanyBrand;
use App\Support\UserVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProspectController extends Controller
{
    public function create(): View
    {
        return view('prospect');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_event' => 'required|string|max:255',
            'date_lamaran' => 'nullable|date',
            'date_akad' => 'nullable|date',
            'date_resepsi' => 'nullable|date',
            'venue' => 'required|string|max:255',
            'name_cpp' => 'required|string|max:255',
            'name_cpw' => 'required|string|max:255',
            'phone' => 'required|regex:/^[0-9]{8,15}$/',
            'address' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $companyId = $this->resolveProspectCompanyId();

        if ($companyId === null) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'company' => 'Form prospek hanya bisa dikirim dari konteks perusahaan yang valid.',
                ]);
        }

        $validated['user_id'] = $this->resolveDefaultOwnerUserId((int) $companyId);
        $validated['company_id'] = (int) $companyId;

        Prospect::withoutGlobalScope('tenant_company')->create($validated);

        return redirect()
            ->route('prospect.success')
            ->with('success', 'Data prospek berhasil dikirim.');
    }

    public function success(): View
    {
        return view('prospect-success');
    }

    private function resolveDefaultOwnerUserId(int $companyId): ?int
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'company_id')) {
            return null;
        }

        $ownerId = User::query()
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->whereNull('created_by')->orWhere('created_by', 0);
            })
            ->orderBy('id')
            ->value('id');

        if ($ownerId) {
            return (int) $ownerId;
        }

        $anyId = User::query()
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->value('id');

        return $anyId ? (int) $anyId : null;
    }

    private function resolveProspectCompanyId(): ?int
    {
        $companyId = UserVisibility::companyId();
        if ($companyId !== null) {
            return $companyId;
        }

        $brandId = CompanyBrand::companyId();
        if ($brandId === null || ! Schema::hasTable('companies')) {
            return null;
        }

        $exists = Company::query()
            ->whereKey($brandId)
            ->when(
                Schema::hasColumn('companies', 'is_active'),
                fn ($q) => $q->where('is_active', true)
            )
            ->exists();

        return $exists ? $brandId : null;
    }
}

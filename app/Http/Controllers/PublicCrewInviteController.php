<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\DataPribadi;
use App\Support\PricingPlans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicCrewInviteController extends Controller
{
    public function show(string $token): View|Response
    {
        $company = Company::findByCrewInviteToken($token);

        if ($company === null || ! static::companyAllowsCrewFreelance($company)) {
            return response()->view('crew.unavailable', status: 404);
        }

        return view('crew.invite', [
            'company' => $company,
            'token' => $token,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse|Response|View
    {
        $company = Company::findByCrewInviteToken($token);

        if ($company === null || ! static::companyAllowsCrewFreelance($company)) {
            return response()->view('crew.unavailable', status: 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('data_pribadis', 'email')->where(fn ($q) => $q->where('company_id', $company->id)),
            ],
            'nomor_telepon' => 'nullable|string|max:20',
            'tanggal_lahir' => 'nullable|date|before_or_equal:today',
            'jenis_kelamin' => 'nullable|string|in:Laki-laki,Perempuan',
            'alamat' => 'nullable|string|max:2000',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
            'pekerjaan' => 'nullable|string|max:255',
            'motivasi_kerja' => 'nullable|string|max:5000',
            'pelatihan' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('crew.invite', ['token' => $token])
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['company_id'] = $company->id;
        unset($data['gaji']);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('data-pribadi-fotos', 'public');
        }

        DataPribadi::withoutGlobalScopes()->create($data);

        return redirect()
            ->route('crew.invite.success', ['token' => $token])
            ->with('success', 'Data crew berhasil dikirim. Terima kasih!');
    }

    public function success(string $token): View|Response
    {
        $company = Company::findByCrewInviteToken($token);

        if ($company === null || ! static::companyAllowsCrewFreelance($company)) {
            return response()->view('crew.unavailable', status: 404);
        }

        return view('crew.success', [
            'company' => $company,
            'token' => $token,
        ]);
    }

    private static function companyAllowsCrewFreelance(Company $company): bool
    {
        return PricingPlans::allows(
            $company->subscription_plan,
            PricingPlans::FEATURE_CREW_FREELANCE
        );
    }
}

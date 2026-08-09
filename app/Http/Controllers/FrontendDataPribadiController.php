<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\DataPribadi;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FrontendDataPribadiController extends Controller
{
    public function create(): View
    {
        return view('data-pribadi.create', [
            'tenantCompanyName' => $this->tenantCompanyName(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = UserVisibility::companyId();

        if (! ProFeatures::actorIsSuperAdmin() && $companyId === null) {
            return redirect()
                ->route('data-pribadi.create')
                ->with('error', 'Akun Anda belum terhubung ke Company. Hubungi admin untuk di-Approve.');
        }

        if ($request->has('gaji')) {
            $request->merge([
                'gaji' => str_replace('.', '', (string) $request->input('gaji')),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('data_pribadis', 'email')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'nomor_telepon' => 'nullable|string|max:20',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|string|in:Laki-laki,Perempuan',
            'alamat' => 'nullable|string',
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
            'pekerjaan' => 'nullable|string|max:255',
            'gaji' => 'nullable|numeric|min:0',
            'motivasi_kerja' => 'nullable|string',
            'pelatihan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('data-pribadi.create')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data = UserVisibility::stampCompanyId($data);

        if (! ProFeatures::actorIsSuperAdmin() && empty($data['company_id'])) {
            return redirect()
                ->route('data-pribadi.create')
                ->with('error', 'Gagal menyimpan: company belum terhubung.')
                ->withInput();
        }

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('data-pribadi-fotos', 'public');
        }

        DataPribadi::create($data);

        return redirect()->route('data-pribadi.success')->with('success', 'Data crew freelance berhasil disimpan!');
    }

    public function index(Request $request): View
    {
        $query = UserVisibility::constrainCompanyQuery(DataPribadi::query());

        if ($request->filled('search')) {
            $searchTerm = (string) $request->search;
            $query->where('nama_lengkap', 'LIKE', '%'.$searchTerm.'%');
        }

        $dataPribadis = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('data-pribadi.index', [
            'dataPribadis' => $dataPribadis,
            'tenantCompanyName' => $this->tenantCompanyName(),
        ]);
    }

    public function success(): View
    {
        return view('data-pribadi.success', [
            'tenantCompanyName' => $this->tenantCompanyName(),
        ]);
    }

    private function tenantCompanyName(): string
    {
        $companyId = UserVisibility::companyId();

        if ($companyId) {
            $name = Company::query()->whereKey($companyId)->value('company_name');

            if (filled($name)) {
                return (string) $name;
            }
        }

        return (string) (config('app.name') ?: 'WOFINS');
    }
}

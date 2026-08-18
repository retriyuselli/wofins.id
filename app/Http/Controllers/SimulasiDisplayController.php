<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\SimulasiProduk;
use App\Models\User;
use App\Support\UserVisibility;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SimulasiDisplayController extends Controller
{
    /**
     * Display the specified simulasi produk.
     */
    public function show(SimulasiProduk $record): View
    {
        Gate::authorize('view', $record);

        $items = collect();
        if ($record->product) {
            $items = $record->product->items()->with('vendor')->get();
        }

        return view('simulasi.show', array_merge(
            $this->companyViewData($record),
            [
                'simulasi' => $record,
                'items' => $items,
                'pengurangans' => $record->pengurangans,
                'pdfMode' => false,
            ],
        ));
    }

    public function downloadPdf(SimulasiProduk $record)
    {
        Gate::authorize('view', $record);

        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');

        $items = collect();
        if ($record->product) {
            $items = $record->product->items()->with('vendor')->get();
        }

        $data = array_merge(
            $this->companyViewData($record, $this->resolveCompanyForDocument($record)),
            [
                'record' => $record,
                'simulasi' => $record,
                'items' => $items,
                'pengurangans' => $record->pengurangans,
                'pdfMode' => true,
            ],
        );

        $pdf = Pdf::loadView('pdf.draft_simulasi', $data);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
            'isFontSubsettingEnabled' => true,
        ]);

        $fileName = 'simulasi_penawaran_'.$record->slug.'_'.now()->format('Ymd').'.pdf';

        return $pdf->download($fileName);
    }

    public function draftKontrak(SimulasiProduk $record)
    {
        Gate::authorize('view', $record);

        $items = collect();
        if ($record->product) {
            $record->product->load([
                'items.vendor.category',
                'penambahanHarga.vendor',
                'pengurangans',
            ]);
            $items = $record->product->items;
        }

        $months = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        $createdAt = $record->created_at
            ? $record->created_at->copy()->setTimezone('Asia/Jakarta')
            : Carbon::now('Asia/Jakarta');

        $currentMonth = $createdAt->month;
        $bulanRomawi = $months[$currentMonth] ?? '';
        $tahun = $createdAt->year;

        $company = $this->resolveCompanyForDocument($record);

        $sequenceQuery = SimulasiProduk::query()
            ->whereYear('created_at', $record->created_at->year)
            ->where('id', '<=', $record->id);

        if ($company?->id) {
            $sequenceQuery->whereHas('user', fn ($q) => $q->where('company_id', $company->id));
        }

        $sequence = $sequenceQuery->count();
        $sequenceFormatted = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);

        $inisialWo = $company?->inisial_wo ?: 'MW';
        $inisialKontrak = $company?->inisial_kontak ?: 'KKP';

        $manualNumber = $record->contract_number;
        if ($manualNumber && str_contains($manualNumber, '/')) {
            $nomorSurat = $manualNumber;
        } else {
            $baseNumber = $manualNumber ?: $sequenceFormatted;
            $nomorSurat = $baseNumber.'/'.$inisialWo.'/'.$inisialKontrak.'/'.$bulanRomawi.'/'.$tahun;
        }

        $financeQuery = User::role('Finance');
        if ($company?->id) {
            $financeQuery->where('company_id', $company->id);
        }
        $financeUser = $financeQuery->first();

        $data = array_merge(
            $this->companyViewData($record, $company),
            [
                'record' => $record,
                'items' => $items,
                'prospect' => $record->prospect,
                'nomorSurat' => $nomorSurat,
                'financeUser' => $financeUser,
            ],
        );

        $pdf = Pdf::loadView('pdf.draft_kontrak', $data);
        $pdf->setPaper('a4', 'portrait');

        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        $fileName = 'Draft_Kontrak_'.$record->slug.'_'.now()->format('Ymd').'.pdf';

        return $pdf->stream($fileName);
    }

    /**
     * Company untuk branding halaman simulasi.
     * Prioritas: company user yang login → company pemilik simulasi (AM).
     */
    protected function resolveCompany(SimulasiProduk $record): ?Company
    {
        $authCompanyId = UserVisibility::companyId(Auth::user());
        if ($authCompanyId) {
            $loggedInCompany = Company::query()
                ->with('paymentMethod')
                ->find($authCompanyId);

            if ($loggedInCompany) {
                return $loggedInCompany;
            }
        }

        return $this->resolveCompanyForDocument($record);
    }

    /**
     * Branding dokumen PDF/klien: selalu company pemilik simulasi (bukan viewer).
     */
    protected function resolveCompanyForDocument(SimulasiProduk $record): ?Company
    {
        $record->loadMissing('user.company.paymentMethod');

        $company = $record->user?->company;
        if ($company) {
            $company->loadMissing('paymentMethod');

            return $company;
        }

        return null;
    }

    /**
     * @return array{
     *     company: ?Company,
     *     companyName: string,
     *     companyAddress: ?string,
     *     companyEmail: ?string,
     *     companyPhone: ?string,
     *     companyWebsite: ?string,
     *     companyLogoUrl: ?string,
     *     companyFaviconUrl: string
     * }
     */
    protected function companyViewData(SimulasiProduk $record, ?Company $company = null): array
    {
        $company ??= $this->resolveCompany($record);

        $logoUrl = null;
        if ($company?->logo_url && Storage::disk('public')->exists($company->logo_url)) {
            $logoUrl = asset('storage/'.ltrim($company->logo_url, '/'));
        }

        $faviconUrl = asset('images/favicon_makna.png');
        if ($company?->favicon_url && Storage::disk('public')->exists($company->favicon_url)) {
            $faviconUrl = asset('storage/'.ltrim($company->favicon_url, '/'));
        }

        $addressParts = array_values(array_filter([
            $company?->address,
            collect([
                $company?->city,
                $company?->province,
                $company?->postal_code,
            ])->filter()->implode(', '),
        ], fn ($part) => filled($part)));

        // Nilai eksplisit agar tidak jatuh ke View::share global (company pertama / Makna).
        return [
            'company' => $company,
            'companyName' => $company?->company_name ?: (string) config('app.name'),
            'companyAddress' => $addressParts !== [] ? implode(', ', $addressParts) : null,
            'companyEmail' => filled($company?->email) ? $company->email : null,
            'companyPhone' => filled($company?->phone) && $company->phone !== '-' ? $company->phone : null,
            'companyWebsite' => filled($company?->website) ? $company->website : null,
            'companyLogoUrl' => $logoUrl,
            'companyFaviconUrl' => $faviconUrl,
        ];
    }
}

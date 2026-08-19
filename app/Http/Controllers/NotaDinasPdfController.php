<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\NotaDinas;
use App\Support\CompanyBrand;
use App\Support\UserVisibility;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class NotaDinasPdfController extends Controller
{
    public function downloadPdf(NotaDinas $notaDinas)
    {
        Gate::authorize('view', $notaDinas);

        $data = $this->buildViewData($notaDinas, forPdf: true);

        $pdf = Pdf::loadView('pdf.nota-dinas-approval', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'approval-'.$notaDinas->no_nd.'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function previewPdf(NotaDinas $notaDinas)
    {
        Gate::authorize('view', $notaDinas);

        $data = $this->buildViewData($notaDinas, forPdf: true);

        $pdf = Pdf::loadView('pdf.nota-dinas-approval', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('preview-'.$notaDinas->no_nd.'.pdf');
    }

    public function previewWeb(NotaDinas $notaDinas)
    {
        Gate::authorize('view', $notaDinas);

        return view('pdf.nota-dinas-preview', $this->buildViewData($notaDinas, forPdf: false));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildViewData(NotaDinas $notaDinas, bool $forPdf): array
    {
        $notaDinas->load([
            'pengirim.company',
            'penerima',
            'approver',
            'details.vendor',
            'details.order.prospect',
        ]);

        $details = $notaDinas->details;
        $company = $this->resolveCompany($notaDinas);
        $logoPath = $this->resolveLogoPath($company);

        return array_merge($this->companyBrandingData($company, $logoPath, $forPdf), [
            'notaDinas' => $notaDinas,
            'details' => $details,
            'totalJumlahTransfer' => $details->sum('jumlah_transfer'),
            'totalByJenis' => $details->groupBy('jenis_pengeluaran')
                ->map(fn ($items) => $items->sum('jumlah_transfer')),
            'totalInvoices' => $details->whereNotNull('invoice_number')->count(),
            'paidInvoices' => $details->where('status_invoice', 'sudah dibayar')->count(),
        ]);
    }

    private function resolveCompany(NotaDinas $notaDinas): ?Company
    {
        if (! Schema::hasTable('companies')) {
            return null;
        }

        $authCompanyId = UserVisibility::companyId(Auth::user());
        if ($authCompanyId) {
            $loggedInCompany = Company::query()->find($authCompanyId);
            if ($loggedInCompany) {
                return $loggedInCompany;
            }
        }

        $senderCompany = $notaDinas->pengirim?->company;
        if ($senderCompany) {
            return $senderCompany;
        }

        $brandCompanyId = CompanyBrand::companyId();
        if ($brandCompanyId) {
            return Company::query()->find($brandCompanyId);
        }

        return null;
    }

    private function resolveLogoPath(?Company $company): string
    {
        if ($company?->logo_url && Storage::disk('public')->exists($company->logo_url)) {
            return Storage::disk('public')->path($company->logo_url);
        }

        return public_path(CompanyBrand::DEFAULT_LOGO);
    }

    /**
     * @return array<string, mixed>
     */
    private function companyBrandingData(?Company $company, string $logoPath, bool $forPdf): array
    {
        $addressParts = array_values(array_filter([
            $company?->address,
            collect([$company?->city, $company?->province, $company?->postal_code])
                ->filter()
                ->implode(', '),
        ]));

        $companyLogoUrl = $company?->logo_url && Storage::disk('public')->exists($company->logo_url)
            ? asset('storage/'.ltrim($company->logo_url, '/'))
            : asset(CompanyBrand::DEFAULT_LOGO);

        return [
            'company' => $company,
            'companyName' => $company?->company_name ?: config('app.name'),
            'companyAddress' => $addressParts !== [] ? implode(', ', $addressParts) : null,
            'companyEmail' => filled($company?->email) ? $company->email : null,
            'companyPhone' => filled($company?->phone) && $company->phone !== '-' ? $company->phone : null,
            'companyLogoUrl' => $companyLogoUrl,
            'logoSrc' => $this->encodeLogoForDisplay($logoPath, $forPdf),
        ];
    }

    private function encodeLogoForDisplay(string $logoPath, bool $forPdf): string
    {
        if (! file_exists($logoPath)) {
            return '';
        }

        try {
            if ($forPdf) {
                $compressed = $this->compressLogoForPdf($logoPath);
                if ($compressed !== null) {
                    return $compressed;
                }
            }

            $mime = mime_content_type($logoPath) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($logoPath));
        } catch (\Throwable) {
            return '';
        }
    }

    private function compressLogoForPdf(string $logoPath): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $binary = @file_get_contents($logoPath);
        if ($binary === false || $binary === '') {
            return null;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return null;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($source);

            return null;
        }

        $maxWidth = 110;
        $dstW = $srcW > $maxWidth ? $maxWidth : $srcW;
        $dstH = (int) max(1, round($srcH * ($dstW / $srcW)));

        $canvas = imagecreatetruecolor($dstW, $dstH);
        if ($canvas === false) {
            imagedestroy($source);

            return null;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $dstW, $dstH, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        ob_start();
        imagejpeg($canvas, null, 82);
        $jpeg = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        if ($jpeg === false || $jpeg === '') {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }
}

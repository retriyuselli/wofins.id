<?php

namespace App\Http\Controllers;

use App\Exports\ProductExport;
use App\Models\Company;
use App\Models\Product;
use App\Services\ProductPricingCalculator;
use App\Support\CompanyBrand;
use App\Support\UserVisibility;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProductDisplayController extends Controller
{
    /**
     * Company untuk branding preview/PDF produk.
     * Prioritas: company user login → company pembuat produk → cookie brand.
     */
    private function resolveCompany(?Product $product = null): ?Company
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

        if ($product) {
            $product->loadMissing('creator.company');
            $ownerCompany = $product->creator?->company;
            if ($ownerCompany) {
                return $ownerCompany;
            }
        }

        $brandCompanyId = CompanyBrand::companyId();
        if ($brandCompanyId) {
            return Company::query()->find($brandCompanyId);
        }

        return null;
    }

    private function companyPreviewData(bool $forPdf = false, ?Product $product = null): array
    {
        $company = $this->resolveCompany($product);

        $logoPath = public_path(CompanyBrand::DEFAULT_LOGO);
        if ($company?->logo_url && Storage::disk('public')->exists($company->logo_url)) {
            $logoPath = Storage::disk('public')->path($company->logo_url);
        }

        $logoSrc = $this->encodeLogoForDisplay($logoPath, $forPdf);

        $addressParts = array_values(array_filter([
            $company?->address,
            collect([$company?->city, $company?->province, $company?->postal_code])
                ->filter()
                ->implode(', '),
        ]));

        return [
            'company' => $company,
            'logoSrc' => $logoSrc,
            'companyName' => $company?->company_name ?: config('app.name'),
            'companyAddress' => $addressParts !== [] ? implode(', ', $addressParts) : null,
            'companyEmail' => filled($company?->email) ? $company->email : null,
            'companyPhone' => filled($company?->phone) && $company->phone !== '-' ? $company->phone : null,
        ];
    }

    /**
     * Embed logo as base64. For PDF, resize/compress first —
     * full-size company logos (~1MB) make DomPDF extremely slow.
     */
    private function encodeLogoForDisplay(?string $logoPath, bool $forPdf = false): string
    {
        if (! is_string($logoPath) || $logoPath === '' || ! file_exists($logoPath)) {
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
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Resize logo to max width ~220px and encode as JPEG for DomPDF.
     */
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

    private function loadProductForDisplay(Product $product): Product
    {
        return $product->load([
            'category',
            'items.vendor',
            'pengurangans',
            'penambahanHarga.vendor',
            'lastEditedBy',
        ]);
    }

    private function productDisplayData(Product $product, bool $forPdf = false): array
    {
        $this->loadProductForDisplay($product);

        return array_merge([
            'product' => $product,
            'pricing' => ProductPricingCalculator::calculateForProduct($product),
        ], $this->companyPreviewData($forPdf, $product));
    }

    private function buildProductPdf(Product $product)
    {
        $data = $this->productDisplayData($product, forPdf: true);

        $pdf = Pdf::loadView('products.pdf', $data);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'dpi' => 96,
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'isFontSubsettingEnabled' => true,
        ]);

        return $pdf;
    }

    public function show(Product $product)
    {
        Gate::authorize('view', $product);

        $product->load(['category', 'items.vendor']);
        $product->image_url = $product->image ? Storage::url($product->image) : asset('images/placeholder-product.png');

        return view('products.detail', compact('product'));
    }

    public function details(Product $product, string $action)
    {
        Gate::authorize('view', $product);

        if ($action === 'download') {
            return $this->downloadPdf($product);
        }

        if ($action === 'preview' || $action === 'print') {
            $viewData = array_merge(
                $this->productDisplayData($product, forPdf: false),
                ['action' => $action]
            );

            return view('products.details-preview', $viewData);
        }

        abort(404, 'Invalid action specified.');
    }

    public function downloadPdf(Product $product)
    {
        Gate::authorize('view', $product);

        $pdf = $this->buildProductPdf($product);
        $fileName = 'product-'.$product->slug.'-'.now()->format('Ymd').'.pdf';

        return $pdf->download($fileName);
    }

    public function exportDetailToExcel(Product $product)
    {
        Gate::authorize('view', $product);

        return Excel::download(
            new ProductExport([$product->id]),
            'product_detail_'.Str::slug($product->name).'_'.now()->format('YmdHis').'.xlsx'
        );
    }
}

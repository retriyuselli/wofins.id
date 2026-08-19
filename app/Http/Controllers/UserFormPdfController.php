<?php

namespace App\Http\Controllers;

use App\Support\CompanyBrand;

class UserFormPdfController extends Controller
{
    /**
     * Display blank user registration form
     */
    public function generateBlankForm()
    {
        $data = array_merge(CompanyBrand::viewData(), [
            'title' => 'FORMULIR PENDATAAN KARYAWAN',
            'company' => CompanyBrand::name(),
            'generated_date' => now()->format('d F Y'),
            'form_number' => 'FRM-HR-'.now()->format('Ymd').'-'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
        ]);

        return view('pdf.user-registration-form', $data);
    }
}

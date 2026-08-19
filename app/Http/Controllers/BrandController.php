<?php

namespace App\Http\Controllers;

use App\Support\CompanyBrand;

class BrandController extends Controller
{
    public function logo()
    {
        return response()->file(CompanyBrand::logoFilePath(), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function favicon()
    {
        return response()->file(CompanyBrand::faviconFilePath(), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function loginImage()
    {
        return response()->file(CompanyBrand::loginImageFilePath(), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}

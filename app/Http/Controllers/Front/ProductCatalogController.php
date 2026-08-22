<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        // Guest: hanya katalog platform (company_id null).
        // User company: produk company sendiri. SA: semua yang approved.
        $productsQuery = Product::query()->where('is_approved', true);

        if (! auth()->check() || (! ProFeatures::actorIsSuperAdmin() && UserVisibility::companyId() === null)) {
            $productsQuery = Product::platformPublic()->where('is_approved', true);
        }

        $products = $productsQuery
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->withoutGlobalScope('tenant_company')
                                ->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->with(['category' => fn ($q) => $q->withoutGlobalScope('tenant_company')])
            ->orderByDesc('created_at')
            ->paginate(16)
            ->withQueryString();

        return view('front.product', [
            'products' => $products,
            'search' => $search,
        ]);
    }
}

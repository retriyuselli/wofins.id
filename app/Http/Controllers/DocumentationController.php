<?php

namespace App\Http\Controllers;

use App\Models\Documentation;
use App\Models\DocumentationCategory;
use Illuminate\Support\Facades\Cache;

class DocumentationController extends Controller
{
    public function index($slug = null)
    {
        $categories = Cache::remember('docs_categories_nav_v1', 300, function () {
            return DocumentationCategory::query()
                ->with(['documentations' => function ($query) {
                    $query->where('is_published', true)->orderBy('order');
                }])
                ->where('is_active', true)
                ->orderBy('order')
                ->get();
        });

        if ($slug) {
            $currentArticle = Documentation::query()
                ->where('slug', $slug)
                ->where('is_published', true)
                ->firstOrFail();

            return view('front.documentation.show', compact('categories', 'currentArticle'));
        }

        return view('front.documentation.index', compact('categories'));
    }
}

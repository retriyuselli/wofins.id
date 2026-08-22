<?php

namespace App\Http\Controllers;

use App\Models\Documentation;
use App\Models\DocumentationCategory;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DocumentationController extends Controller
{
    public function index($slug = null)
    {
        $scope = $this->docsCacheScope();

        $categories = Cache::remember('docs_categories_nav_v1:'.$scope, 300, function () {
            return $this->docsCategoryQuery()
                ->with(['documentations' => function ($query) {
                    $this->applyDocsVisibility($query);
                    $query->where('is_published', true)->orderBy('order');
                }])
                ->where('is_active', true)
                ->orderBy('order')
                ->get();
        });

        if ($slug) {
            $currentArticle = $this->docsArticleQuery()
                ->where('slug', $slug)
                ->where('is_published', true)
                ->firstOrFail();

            return view('front.documentation.show', compact('categories', 'currentArticle'));
        }

        return view('front.documentation.index', compact('categories'));
    }

    private function docsCacheScope(): string
    {
        if (! auth()->check()) {
            return 'platform';
        }

        return UserVisibility::cacheScopeKey();
    }

    /**
     * Guest: hanya platform (company_id null).
     * Company user: platform + docs company sendiri.
     * SA: semua.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function applyDocsVisibility(Builder $query): void
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return;
        }

        $companyId = UserVisibility::companyId();
        $table = $query->getModel()->getTable();

        $query->withoutGlobalScope('tenant_company');

        if ($companyId === null) {
            $query->whereNull($table.'.company_id');

            return;
        }

        $query->where(function (Builder $q) use ($table, $companyId) {
            $q->whereNull($table.'.company_id')
                ->orWhere($table.'.company_id', $companyId);
        });
    }

    /**
     * @return Builder<DocumentationCategory>
     */
    private function docsCategoryQuery(): Builder
    {
        $query = DocumentationCategory::query();
        $this->applyDocsVisibility($query);

        return $query;
    }

    /**
     * @return Builder<Documentation>
     */
    private function docsArticleQuery(): Builder
    {
        $query = Documentation::query();
        $this->applyDocsVisibility($query);

        return $query;
    }
}

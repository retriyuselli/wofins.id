<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Document;
use App\Support\UserVisibility;
use Illuminate\Support\Facades\Auth;

class DocumentObserver
{
    public function creating(Document $document): void
    {
        if (empty($document->created_by) && Auth::check()) {
            $document->created_by = Auth::id();
        }

        if (empty($document->company_id)) {
            $companyId = UserVisibility::companyId();
            if ($companyId !== null) {
                $document->company_id = $companyId;
            }
        }

        if (empty($document->document_number) && $document->category_id) {
            $document->loadMissing('category');
            if ($document->category) {
                $document->document_number = $this->generateDocumentNumber($document);
            }
        }
    }

    protected function generateDocumentNumber(Document $document): string
    {
        $category = $document->category;
        $format = $category->format_number ?: '{SEQ}/{CAT}/{CO}/{ROMAN_MONTH}/{Y}';

        $companyCode = 'DOC';
        $companyId = $document->company_id ? (int) $document->company_id : UserVisibility::companyId();
        if ($companyId) {
            $company = Company::query()->find($companyId);
            if ($company) {
                $companyCode = $company->documentCode();
            }
        }

        $replacements = [
            '{Y}' => now()->year,
            '{M}' => now()->format('m'),
            '{ROMAN_MONTH}' => $this->getRomanMonth(now()->month),
            '{CAT}' => $category->code ?? 'DOC',
            '{DEPT}' => 'GEN',
            '{CO}' => $companyCode,
        ];

        $number = str_replace(array_keys($replacements), array_values($replacements), $format);

        if (str_contains($number, '{SEQ}')) {
            $seqQuery = Document::withoutGlobalScope('tenant_company')
                ->where('category_id', $category->id)
                ->whereYear('created_at', now()->year);

            if ($document->company_id) {
                $seqQuery->where('company_id', $document->company_id);
            }

            $count = $seqQuery->count();
            $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $number = str_replace('{SEQ}', $sequence, $number);
        }

        return $number;
    }

    protected function getRomanMonth(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $map[$month] ?? '';
    }
}

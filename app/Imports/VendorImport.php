<?php

namespace App\Imports;

use App\Enums\StatusVendor;
use App\Models\Category;
use App\Models\Company;
use App\Models\Vendor;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VendorImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    use Importable;

    private int $rowCount = 0;

    private int $skippedQuota = 0;

    private int $skippedInvalid = 0;

    private int $skippedDuplicate = 0;

    private int $skippedCategory = 0;

    private int $skippedParent = 0;

    public function __construct(private int $companyId)
    {
    }

    public function collection(Collection $rows): void
    {
        if (! $this->companyAllowed()) {
            return;
        }

        $pendingParents = [];

        foreach ($rows as $row) {
            $row = $this->normalizeRow($row->toArray());
            $name = $this->cell($row, 'name', 'nama');

            if (blank($name)) {
                $this->skippedInvalid++;

                continue;
            }

            if ($this->vendorExistsInCompany((string) $name)) {
                $this->skippedDuplicate++;

                continue;
            }

            if (! CompanySubscription::canCreate(CompanySubscription::RESOURCE_VENDORS)
                && ! ProFeatures::actorIsSuperAdmin()) {
                $this->skippedQuota++;

                continue;
            }

            $categoryName = $this->cell($row, 'category', 'kategori', 'category_name');
            $category = $this->findCategoryInCompany($categoryName);

            if (! $category) {
                $this->skippedCategory++;

                continue;
            }

            $status = $this->resolveStatus($row);
            $parentName = trim((string) ($this->cell($row, 'parent', 'vendor_induk', 'parent_name') ?? ''));

            if ($parentName !== '' && $status === StatusVendor::PRODUCT) {
                $pendingParents[Str::lower((string) $name)] = $parentName;
            }

            $payload = UserVisibility::stampTeamOwner([
                'company_id' => $this->companyId,
                'name' => (string) $name,
                'slug' => $this->uniqueSlug((string) $name),
                'phone' => $this->cell($row, 'phone', 'telepon', 'no_hp', 'hp'),
                'pic_name' => $this->cell($row, 'pic_name', 'pic', 'nama_pic'),
                'address' => $this->cell($row, 'address', 'alamat'),
                'status' => $status,
                'is_master' => $status === StatusVendor::MASTER,
                'is_published' => $this->resolvePublished($row),
                'stock' => (int) ($this->cell($row, 'stock', 'stok') ?? 0),
                'harga_publish' => (int) round($this->transformAmount($this->cell($row, 'harga_publish', 'harga_jual'))),
                'harga_vendor' => (int) round($this->transformAmount($this->cell($row, 'harga_vendor', 'harga_modal'))),
                'bank_name' => $this->cell($row, 'bank_name', 'bank'),
                'bank_account' => $this->cell($row, 'bank_account', 'no_rekening', 'rekening'),
                'account_holder' => $this->cell($row, 'account_holder', 'nama_rekening', 'atas_nama'),
                'category_id' => $category->id,
                'parent_id' => null,
            ], 'created_by');

            if (empty($payload['created_by']) && Auth::id()) {
                $payload['created_by'] = Auth::id();
            }

            Vendor::query()->create($payload);
            $this->rowCount++;
        }

        $this->attachParents($pendingParents);
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getSkippedQuota(): int
    {
        return $this->skippedQuota;
    }

    public function getSkippedInvalid(): int
    {
        return $this->skippedInvalid;
    }

    public function getSkippedDuplicate(): int
    {
        return $this->skippedDuplicate;
    }

    public function getSkippedCategory(): int
    {
        return $this->skippedCategory;
    }

    public function getSkippedParent(): int
    {
        return $this->skippedParent;
    }

    /**
     * @param  array<string, string>  $pendingParents  lowercase vendor name => parent name
     */
    private function attachParents(array $pendingParents): void
    {
        foreach ($pendingParents as $childName => $parentName) {
            $child = Vendor::query()
                ->where('company_id', $this->companyId)
                ->whereRaw('LOWER(name) = ?', [$childName])
                ->first();

            $parent = $this->findParentInCompany($parentName);

            if (! $child || ! $parent || (int) $child->id === (int) $parent->id) {
                $this->skippedParent++;

                continue;
            }

            $child->parent_id = $parent->id;
            $child->save();
        }
    }

    private function companyAllowed(): bool
    {
        if ($this->companyId <= 0) {
            return false;
        }

        if (! Company::query()->whereKey($this->companyId)->exists()) {
            return false;
        }

        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        return UserVisibility::ownsCompanyId($this->companyId);
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (Vendor::query()
            ->withoutGlobalScope('tenant_company')
            ->where('slug', $slug)
            ->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function vendorExistsInCompany(string $name): bool
    {
        return Vendor::query()
            ->where('company_id', $this->companyId)
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->exists();
    }

    private function findCategoryInCompany(mixed $name): ?Category
    {
        if (blank($name)) {
            return null;
        }

        $needle = Str::lower(trim((string) $name));
        $slug = Str::slug((string) $name);

        return Category::query()
            ->where('company_id', $this->companyId)
            ->where(function ($query) use ($needle, $slug) {
                $query->whereRaw('LOWER(name) = ?', [$needle])
                    ->orWhereRaw('LOWER(slug) = ?', [Str::lower($slug)]);
            })
            ->first();
    }

    private function findParentInCompany(string $name): ?Vendor
    {
        return Vendor::query()
            ->where('company_id', $this->companyId)
            ->whereNull('parent_id')
            ->whereIn('status', [StatusVendor::VENDOR, StatusVendor::MASTER])
            ->whereRaw('LOWER(name) = ?', [Str::lower(trim($name))])
            ->first();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveStatus(array $row): StatusVendor
    {
        $raw = Str::lower(trim((string) ($this->cell($row, 'status') ?? '')));
        $parent = trim((string) ($this->cell($row, 'parent', 'vendor_induk', 'parent_name') ?? ''));

        return match (true) {
            in_array($raw, ['vendor', 'induk'], true) => StatusVendor::VENDOR,
            in_array($raw, ['master'], true) => StatusVendor::MASTER,
            in_array($raw, ['product', 'produk', 'item'], true) => StatusVendor::PRODUCT,
            $parent !== '' => StatusVendor::PRODUCT,
            default => StatusVendor::PRODUCT,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolvePublished(array $row): bool
    {
        $raw = Str::lower(trim((string) ($this->cell($row, 'is_published', 'published', 'publish') ?? '')));

        return in_array($raw, ['1', 'true', 'ya', 'yes'], true);
    }

    /**
     * @param  array<int|string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[Str::lower(trim((string) $key))] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function cell(array $row, string ...$keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    private function transformAmount(mixed $amount): float
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        if (is_string($amount)) {
            $amount = preg_replace('/[^0-9.-]/', '', $amount);
        }

        return (float) $amount;
    }
}

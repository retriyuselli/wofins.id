<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Company;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Support\UserVisibility;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Invoice extends Page
{
    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.resources.order-resource.pages.invoice';

    protected static ?string $title = 'Detail';

    protected static ?string $slug = 'details';

    public Order $order;

    public function mount(int|string $record): void
    {
        $this->order = Order::with([
            'prospect',
            'user.company.paymentMethod',
            'employee',
            'items.product.vendorItems.vendor',
            'items.product.items',
            'items.product.penambahanHarga',
            'items.product.pengurangans',
            'dataPembayaran.paymentMethod',
            'expenses.vendor',
        ])->findOrFail($record);
    }

    protected function getViewData(): array
    {
        $company = $this->resolveCompany();

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
            collect([$company?->city, $company?->province, $company?->postal_code])
                ->filter()
                ->implode(', '),
        ]));

        $paymentMethodsQuery = PaymentMethod::query()->where('is_cash', false);
        if ($company?->id && Schema::hasColumn('payment_methods', 'company_id')) {
            $paymentMethodsQuery->where('company_id', $company->id);
        }

        return [
            'company' => $company,
            'companyName' => $company?->company_name ?: config('app.name'),
            'companyAddress' => $addressParts !== [] ? implode(' · ', $addressParts) : null,
            'companyEmail' => $company?->email,
            'companyPhone' => $company?->phone,
            'companyWebsite' => $company?->website,
            'companyLogoUrl' => $logoUrl,
            'companyFaviconUrl' => $faviconUrl,
            'paymentMethods' => $paymentMethodsQuery->get(),
            'allExpenses' => $this->order->expenses->sortByDesc('date_expense'),
            'totalVendor' => $this->order->expenses->sum('amount'),
        ];
    }

    protected function resolveCompany(): ?Company
    {
        if (! Schema::hasTable('companies')) {
            return null;
        }

        $authCompanyId = UserVisibility::companyId(Auth::user());
        if ($authCompanyId) {
            $loggedIn = Company::query()->with('paymentMethod')->find($authCompanyId);
            if ($loggedIn) {
                return $loggedIn;
            }
        }

        $this->order->loadMissing('user.company.paymentMethod');

        return $this->order->user?->company;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Invoice '.($this->order->prospect->name_event ?? $this->order->number);
    }
}

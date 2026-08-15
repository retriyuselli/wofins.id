<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Company extends Model
{
    use LogsActivity;

    protected static function booted(): void
    {
        static::created(function (Company $company) {
            \App\Support\DefaultCategories::ensureForCompany($company);
        });

        static::saved(function (Company $company) {
            \App\Support\CompanySubscription::forgetCache($company->id);
        });

        static::deleted(function (Company $company) {
            \App\Support\CompanySubscription::forgetCache($company->id);
        });
    }

    protected $fillable = [
        'company_name',
        'business_license',
        'owner_name',
        'jabatan_owner',
        'inisial_wo',
        'inisial_kontak',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'postal_code',
        'website',
        'description',
        'logo_url',
        'favicon_url',
        'image_login',
        'established_year',
        'employee_count',
        'legal_entity_type',
        'deed_of_establishment',
        'deed_date',
        'notary_name',
        'notary_license_number',
        'nib_number',
        'nib_issued_date',
        'nib_valid_until',
        'npwp_number',
        'npwp_issued_date',
        'tax_office',
        'legal_documents',
        'legal_document_status',
        'payment_method_id',
        'subscription_plan',
        'seat_limit_override',
        'vendor_limit_override',
        'product_limit_override',
        'order_limit_override',
        'prospect_limit_override',
        'simulasi_limit_override',
        'payment_method_limit_override',
        'fixed_asset_limit_override',
        'piutang_limit_override',
        'pembayaran_piutang_limit_override',
        'category_limit_override',
        'data_pembayaran_limit_override',
        'expense_limit_override',
        'expense_ops_limit_override',
        'pendapatan_lain_limit_override',
        'pengeluaran_lain_limit_override',
        'subscription_expires_at',
        'is_active',
        'deactivated_at',
        'deactivated_by',
        'crew_invite_token',
        'crew_invite_enabled',
        'crew_invite_rotated_at',
    ];

    protected $casts = [
        'established_year' => 'integer',
        'employee_count' => 'integer',
        'deed_date' => 'date',
        'nib_issued_date' => 'date',
        'nib_valid_until' => 'date',
        'npwp_issued_date' => 'date',
        'legal_documents' => 'array',
        'seat_limit_override' => 'integer',
        'vendor_limit_override' => 'integer',
        'product_limit_override' => 'integer',
        'order_limit_override' => 'integer',
        'prospect_limit_override' => 'integer',
        'simulasi_limit_override' => 'integer',
        'payment_method_limit_override' => 'integer',
        'fixed_asset_limit_override' => 'integer',
        'piutang_limit_override' => 'integer',
        'pembayaran_piutang_limit_override' => 'integer',
        'category_limit_override' => 'integer',
        'data_pembayaran_limit_override' => 'integer',
        'expense_limit_override' => 'integer',
        'expense_ops_limit_override' => 'integer',
        'pendapatan_lain_limit_override' => 'integer',
        'pengeluaran_lain_limit_override' => 'integer',
        'subscription_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
        'crew_invite_enabled' => 'boolean',
        'crew_invite_rotated_at' => 'datetime',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['company_name', 'owner_name', 'email', 'phone', 'is_active', 'subscription_plan'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('company');
    }

    public function getFaviconPathAttribute(): string
    {
        if ($this->favicon_url && Storage::disk('public')->exists($this->favicon_url)) {
            return Storage::disk('public')->path($this->favicon_url);
        }

        return public_path('images/favicon_makna.png');
    }

    public function getFaviconPublicUrlAttribute(): string
    {
        if ($this->favicon_url && Storage::disk('public')->exists($this->favicon_url)) {
            return Storage::disk('public')->url($this->favicon_url);
        }

        return asset('images/favicon_makna.png');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function deactivatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    public function isActive(): bool
    {
        if (Schema::hasColumn('companies', 'is_active')) {
            return (bool) $this->is_active;
        }

        return true;
    }

    public function isDeactivated(): bool
    {
        return ! $this->isActive();
    }

    public function prospectApps(): HasMany
    {
        return $this->hasMany(ProspectApp::class);
    }

    public function dataPribadis(): HasMany
    {
        return $this->hasMany(DataPribadi::class);
    }

    public function isCrewInviteOpen(): bool
    {
        if (! Schema::hasColumn('companies', 'crew_invite_enabled')) {
            return false;
        }

        return (bool) $this->crew_invite_enabled && filled($this->crew_invite_token);
    }

    public function ensureCrewInviteToken(): string
    {
        if (! Schema::hasColumn('companies', 'crew_invite_token')) {
            return '';
        }

        if (filled($this->crew_invite_token)) {
            return (string) $this->crew_invite_token;
        }

        return $this->regenerateCrewInviteToken();
    }

    public function regenerateCrewInviteToken(): string
    {
        if (! Schema::hasColumn('companies', 'crew_invite_token')) {
            return '';
        }

        do {
            $token = Str::random(48);
        } while (static::query()->where('crew_invite_token', $token)->exists());

        $this->forceFill([
            'crew_invite_token' => $token,
            'crew_invite_enabled' => true,
            'crew_invite_rotated_at' => now(),
        ])->save();

        return $token;
    }

    public function crewInviteUrl(): ?string
    {
        $token = $this->ensureCrewInviteToken();

        if ($token === '') {
            return null;
        }

        return route('crew.invite', ['token' => $token]);
    }

    public static function findByCrewInviteToken(string $token): ?self
    {
        if ($token === '' || ! Schema::hasColumn('companies', 'crew_invite_token')) {
            return null;
        }

        return static::query()
            ->where('crew_invite_token', $token)
            ->where('crew_invite_enabled', true)
            ->first();
    }
}

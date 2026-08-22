<?php

namespace App\Models;

use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Category extends Model
{
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_company', function (Builder $builder) {
            if (! Schema::hasColumn('categories', 'company_id')) {
                return;
            }

            if (ProFeatures::actorIsSuperAdmin()) {
                return;
            }

            $companyId = UserVisibility::companyId();

            if ($companyId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where('company_id', $companyId);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'is_active', 'company_id'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('category');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}

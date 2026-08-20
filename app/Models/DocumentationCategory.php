<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DocumentationCategory extends Model
{
    use BelongsToCompany;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'icon',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'is_active'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('documentation_category');
    }

    public function documentations(): HasMany
    {
        return $this->hasMany(Documentation::class);
    }
}

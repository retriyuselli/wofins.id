<?php

namespace App\Models;

use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Document extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'category_id',
        'document_number',
        'title',
        'summary',
        'content',
        'metadata',
        'date_effective',
        'date_expired',
        'status',
        'confidentiality',
        'created_by',
        'use_digital_signature',
        'show_confidentiality_warning',
    ];

    protected $casts = [
        'metadata' => 'array',
        'date_effective' => 'date',
        'date_expired' => 'date',
        'use_digital_signature' => 'boolean',
        'show_confidentiality_warning' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_company', function (Builder $builder) {
            if (! Schema::hasColumn('documents', 'company_id')) {
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
            ->logOnly(['title', 'document_number', 'category_id', 'company_id', 'date_effective'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('document');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class);
    }

    public function recipientsList(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_recipients', 'document_id', 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class)->orderBy('step_order');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(DocumentRecipient::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class);
    }
}

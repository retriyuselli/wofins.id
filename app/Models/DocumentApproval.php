<?php

namespace App\Models;

use App\Support\CompanySubscription;
use App\Support\PricingPlans;
use App\Support\ProFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DocumentApproval extends Model
{
    use LogsActivity;

    protected $fillable = [
        'document_id',
        'user_id',
        'step_order',
        'status',
        'note',
        'signed_at',
        'signature_path',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (DocumentApproval $approval): void {
            if ((int) $approval->step_order <= 1) {
                return;
            }

            // Seeder / proses tanpa user login: jangan blokir.
            if (! Auth::check()) {
                return;
            }

            if (ProFeatures::allows(PricingPlans::FEATURE_MULTI_APPROVAL)) {
                return;
            }

            throw ValidationException::withMessages([
                'step_order' => CompanySubscription::upgradeMessage(PricingPlans::FEATURE_MULTI_APPROVAL),
            ]);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['document_id', 'user_id', 'status', 'note', 'signed_at'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('document_approval');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

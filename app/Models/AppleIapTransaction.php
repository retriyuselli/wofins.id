<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppleIapTransaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'original_transaction_id',
        'user_id',
        'company_id',
        'product_id',
        'plan_key',
        'billing',
        'environment',
        'bundle_id',
        'purchased_at',
        'expires_at',
        'revocation_reason',
        'revoked_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

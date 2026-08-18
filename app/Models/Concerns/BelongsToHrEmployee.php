<?php

namespace App\Models\Concerns;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToHrEmployee
{
    public static function bootBelongsToHrEmployee(): void
    {
        static::saving(function ($model): void {
            if (! $model->employee_id) {
                return;
            }

            // Sinkronkan user_id denormalisasi dari Employee (nullable jika tanpa login).
            if (! $model->isDirty('user_id') || $model->user_id === null) {
                $model->user_id = Employee::withoutGlobalScopes()->whereKey($model->employee_id)->value('user_id');
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

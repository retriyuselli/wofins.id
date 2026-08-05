<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LeaveBalance */
class LeaveBalanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'leave_type' => $this->whenLoaded('leaveType', fn () => [
                'id' => $this->leaveType?->id,
                'name' => $this->leaveType?->name,
                'keterangan' => $this->leaveType?->keterangan,
                'max_days_per_year' => $this->leaveType?->max_days_per_year,
            ]),
            'allocated_days' => (int) $this->allocated_days,
            'carried_over_days' => (int) ($this->carried_over_days ?? 0),
            'used_days' => (int) $this->used_days,
            'remaining_days' => (int) $this->remaining_days,
        ];
    }
}

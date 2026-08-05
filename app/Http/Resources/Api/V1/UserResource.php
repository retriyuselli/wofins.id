<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatarPath = $this->avatar_url;

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'date_of_birth' => optional($this->date_of_birth)?->toDateString(),
            'gender' => $this->gender,
            'department' => $this->department,
            'hire_date' => optional($this->hire_date)?->toDateString(),
            'emergency_contact' => $this->emergency_contact,
            'status' => $this->status,
            'avatar_url' => $avatarPath
                ? url(Storage::url($avatarPath))
                : null,
            'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()->values()->all()),
            'expire_date' => optional($this->expire_date)?->toIso8601String(),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'days_until_expiration' => $this->getDaysUntilExpiration(),
        ];
    }
}

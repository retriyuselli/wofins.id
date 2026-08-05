<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\LeaveRequest */
class LeaveRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $documents = $this->documents;
        if (is_string($documents)) {
            $documents = json_decode($documents, true) ?: [];
        }
        if (! is_array($documents)) {
            $documents = [];
        }

        return [
            'id' => $this->id,
            'leave_type' => $this->whenLoaded('leaveType', fn () => [
                'id' => $this->leaveType?->id,
                'name' => $this->leaveType?->name,
                'keterangan' => $this->leaveType?->keterangan,
            ]),
            'start_date' => optional($this->start_date)?->toDateString(),
            'end_date' => optional($this->end_date)?->toDateString(),
            'total_days' => $this->total_days,
            'reason' => $this->reason,
            'emergency_contact' => $this->emergency_contact,
            'status' => $this->status,
            'documents' => collect($documents)->map(function ($path) {
                return [
                    'path' => $path,
                    'url' => $path ? url(Storage::url($path)) : null,
                ];
            })->values()->all(),
            'replacement_employee' => $this->whenLoaded('replacementEmployee', fn () => $this->replacementEmployee ? [
                'id' => $this->replacementEmployee->id,
                'name' => $this->replacementEmployee->name,
            ] : null),
            'approver' => $this->whenLoaded('approver', fn () => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ] : null),
            'approval_notes' => $this->approval_notes,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}

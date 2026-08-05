<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Absensi */
class AbsensiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal' => optional($this->tanggal)?->toDateString(),
            'status' => $this->status,
            'jam_masuk' => optional($this->jam_masuk)?->toIso8601String(),
            'jam_pulang' => optional($this->jam_pulang)?->toIso8601String(),
            'menit_kerja' => $this->menit_kerja,
            'menit_terlambat' => $this->menit_terlambat,
            'menit_pulang_cepat' => $this->menit_pulang_cepat,
            'sumber' => $this->sumber,
            'catatan' => $this->catatan,
            'sudah_masuk' => (bool) $this->jam_masuk,
            'sudah_pulang' => (bool) $this->jam_pulang,
            'logs' => $this->whenLoaded('logAbsensis', fn () => $this->logAbsensis->map(fn ($log) => [
                'id' => $log->id,
                'jenis' => $log->jenis,
                'waktu' => optional($log->waktu)?->toIso8601String(),
                'lintang' => $log->lintang,
                'bujur' => $log->bujur,
                'jarak_ke_kantor_meter' => $log->jarak_ke_kantor_meter,
                'dalam_radius' => $log->dalam_radius,
                'foto_url' => $log->temporaryFotoUrl(now()->addHours(12)),
                'valid' => $log->valid,
            ])->values()),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}

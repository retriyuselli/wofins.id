<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LokasiAbsensi */
class LokasiAbsensiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'lintang' => (float) $this->lintang,
            'bujur' => (float) $this->bujur,
            'radius_meter' => (int) $this->radius_meter,
            'alamat' => $this->alamat,
            'urutan' => (int) $this->urutan,
        ];
    }
}

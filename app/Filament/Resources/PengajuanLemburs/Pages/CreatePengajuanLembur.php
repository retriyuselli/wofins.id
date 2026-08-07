<?php

namespace App\Filament\Resources\PengajuanLemburs\Pages;

use App\Filament\Resources\PengajuanLemburs\PengajuanLemburResource;
use App\Models\User;
use App\Services\PengajuanLemburService;
use App\Support\UserVisibility;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreatePengajuanLembur extends CreateRecord
{
    protected static string $resource = PengajuanLemburResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $user */
        $user = User::query()->findOrFail($data['user_id']);

        if (! UserVisibility::canAccessUser($user)) {
            throw ValidationException::withMessages([
                'user_id' => 'Anda tidak berwenang mengajukan lembur untuk user lain.',
            ]);
        }

        return app(PengajuanLemburService::class)->ajukan($user, $data);
    }
}

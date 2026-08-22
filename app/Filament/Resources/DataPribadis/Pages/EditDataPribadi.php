<?php

namespace App\Filament\Resources\DataPribadis\Pages;

use App\Filament\Resources\DataPribadis\DataPribadiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDataPribadi extends EditRecord
{
    protected static string $resource = DataPribadiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation()
                ->modalHeading('Hapus crew freelance?')
                ->modalDescription('Data akan dipindah ke sampah. Company Anda bisa memulihkannya dari filter terhapus.')
                ->modalSubmitActionLabel('Ya, hapus'),
        ];
    }
}

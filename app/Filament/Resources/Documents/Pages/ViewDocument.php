<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Preview PDF')
                ->icon('heroicon-o-printer')
                ->url(fn ($record) => route('document.stream', $record))
                ->openUrlInNewTab(),
            Action::make('edit')
                ->label('Edit')
                ->visible(fn ($record): bool => DocumentResource::canEdit($record))
                ->url(fn ($record) => DocumentResource::getUrl('edit', ['record' => $record])),
        ];
    }
}

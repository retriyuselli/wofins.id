<?php

namespace App\Filament\Resources\Prospects\Pages;

use App\Filament\Resources\Prospects\ProspectResource;
use App\Models\Prospect;
use App\Support\UserVisibility;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViewProspect extends ListRecords
{
    protected static string $resource = ProspectResource::class;

    public function getTitle(): string
    {
        $recordId = request()->query('record');
        if (filled($recordId) && is_numeric($recordId)) {
            $prospect = $this->baseProspectQuery()
                ->whereKey((int) $recordId)
                ->first();

            return $prospect?->name_event
                ? 'Prospek: '.$prospect->name_event
                : 'Prospek';
        }

        return match ((string) request()->query('metric')) {
            'with_orders' => 'Prospek dengan Order',
            'month' => 'Prospek Bulan Ini',
            'week' => 'Prospek Minggu Ini',
            'today' => 'Prospek Hari Ini',
            default => 'Prospek',
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_prospects')
                ->label('Kembali')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(fn () => ProspectResource::getUrl('index')),
        ];
    }

    public function table(Table $table): Table
    {
        return ProspectResource::table($table)
            ->filters([])
            ->toolbarActions([]);
    }

    protected function getTableQuery(): Builder
    {
        $query = $this->baseProspectQuery();

        // ViewAction Filament mengirim ?record=ID — tampilkan prospek itu (scoped company).
        $recordId = request()->query('record');
        if (filled($recordId) && is_numeric($recordId)) {
            return $query->whereKey((int) $recordId);
        }

        $metric = (string) request()->query('metric');

        return match ($metric) {
            'with_orders' => $query->whereHas('orders'),
            'month' => $query->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ]),
            'week' => $query->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]),
            'today' => $query->whereDate('created_at', Carbon::today()),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Super admin: semua. User company aktif: hanya prospek tim/company-nya.
     */
    protected function baseProspectQuery(): Builder
    {
        return UserVisibility::constrainOwnedQuery(
            Prospect::query()
                ->withTrashed()
                ->with(['user:id,name', 'latestOrder'])
        );
    }
}

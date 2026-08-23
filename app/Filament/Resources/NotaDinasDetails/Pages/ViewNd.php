<?php

namespace App\Filament\Resources\NotaDinasDetails\Pages;

use App\Filament\Resources\NotaDinasDetails\NotaDinasDetailResource;
use App\Filament\Resources\NotaDinas\NotaDinasResource;
use App\Models\NotaDinas;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewNd extends Page
{
    protected static string $resource = NotaDinasDetailResource::class;

    protected string $view = 'filament.resources.nota-dinas-detail-resource.pages.view-nd';

    protected static ?string $title = 'Surat Persetujuan';

    protected static ?string $slug = 'view-nd';

    public NotaDinas $notaDinas;

    public $notaDinasDetails;

    public function mount(int|string $record): void
    {
        $this->notaDinas = NotaDinasResource::getEloquentQuery()
            ->with([
                'pengirim',
                'penerima',
                'approver',
                'details.vendor',
                'details.order.prospect',
            ])
            ->findOrFail($record);

        abort_unless(UserVisibility::ownsCompanyId($this->notaDinas->company_id !== null ? (int) $this->notaDinas->company_id : null), 403);

        $this->notaDinasDetails = $this->notaDinas->details;
    }

    protected function getViewData(): array
    {
        // Calculate totals
        $totalJumlahTransfer = $this->notaDinasDetails->sum('jumlah_transfer');
        $totalByJenis = $this->notaDinasDetails->groupBy('jenis_pengeluaran')
            ->map(fn ($items) => $items->sum('jumlah_transfer'));

        return [
            'totalJumlahTransfer' => $totalJumlahTransfer,
            'totalByJenis' => $totalByJenis,
            'details' => $this->notaDinasDetails,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Surat Persetujuan - '.$this->notaDinas->no_nd;
    }

    protected function getActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function () {
                    // TODO: Implement PDF download functionality
                    Notification::make()
                        ->title('PDF Download')
                        ->body('PDF download functionality will be implemented soon.')
                        ->info()
                        ->send();
                }),

            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => $this->js('window.print()')),
        ];
    }
}

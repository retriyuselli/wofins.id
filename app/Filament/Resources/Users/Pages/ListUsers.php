<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\UserExpirationOverview;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Header hanya kuota pengguna (ringkas). Matriks lengkap ada di widget Dashboard.
        if (UserVisibility::canViewTeamSeatSummary()) {
            $canAddUser = UserVisibility::canCreateTeamUser();

            $actions[] = Action::make('subscription_seats')
                ->label(CompanySubscription::seatSummary())
                ->icon('heroicon-o-ticket')
                ->color($canAddUser ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $canCreate = UserResource::canCreate() && Auth::user()?->can('Create:User');

        $actions[] = CreateAction::make()
            ->visible(fn () => (bool) Auth::user()?->can('Create:User'))
            ->disabled(fn () => ! $canCreate)
            ->tooltip(function () use ($canCreate): ?string {
                if ($canCreate) {
                    return null;
                }

                if (! UserVisibility::isTeamOwner() && ! UserVisibility::actorIsSuperAdmin()) {
                    return 'Hanya pemilik paket yang dapat menambah pengguna.';
                }

                return CompanySubscription::seatUpgradeHint();
            });

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UserExpirationOverview::class,
        ];
    }
}

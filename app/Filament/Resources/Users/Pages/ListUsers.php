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

        if (UserVisibility::canViewTeamSeatSummary()) {
            $actions[] = Action::make('subscription_seats')
                ->label(
                    UserVisibility::actorIsSuperAdmin()
                        ? CompanySubscription::quotasOverview()
                        : ('Tim: '.CompanySubscription::seatSummary())
                )
                ->icon('heroicon-o-ticket')
                ->color(CompanySubscription::hasSeatAvailable() ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $actions[] = CreateAction::make()
            ->visible(fn () => Auth::user()?->can('Create:User'))
            ->disabled(fn () => ! CompanySubscription::hasSeatAvailable()
                && ! UserVisibility::actorIsSuperAdmin())
            ->tooltip(fn () => CompanySubscription::hasSeatAvailable()
                || UserVisibility::actorIsSuperAdmin()
                ? null
                : CompanySubscription::seatFullMessage());

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UserExpirationOverview::class,
        ];
    }
}

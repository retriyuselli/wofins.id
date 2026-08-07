<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use App\Support\UserVisibility;
use Carbon\Carbon;
use Exception;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserExpirationOverview extends BaseWidget
{
    protected function getStats(): array
    {
        try {
            $base = UserVisibility::constrainUsersQuery(User::query());

            $expiredUsers = (clone $base)->whereNotNull('expire_date')
                ->where('expire_date', '<=', Carbon::now())
                ->count();

            $expiringSoonUsers = (clone $base)->whereNotNull('expire_date')
                ->whereBetween('expire_date', [
                    Carbon::now(),
                    Carbon::now()->addDays(7),
                ])->count();

            $activeUsers = (clone $base)->where(function ($query) {
                $query->whereNull('expire_date')
                    ->orWhere('expire_date', '>', Carbon::now());
            })->count();

            $totalUsers = (clone $base)->count();

            $scopeLabel = UserVisibility::actorIsSuperAdmin()
                ? 'Total semua pengguna'
                : 'Total pengguna di tim Anda';

            return [
                Stat::make('Total User', $totalUsers)
                    ->description($scopeLabel)
                    ->descriptionIcon('heroicon-m-users')
                    ->color('info'),

                Stat::make('User Aktif', $activeUsers)
                    ->description('Pengguna yang masih aktif')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),

                Stat::make('Akan Kedaluwarsa', $expiringSoonUsers)
                    ->description('Dalam 7 hari ke depan')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('warning'),

                Stat::make('Sudah Kedaluwarsa', $expiredUsers)
                    ->description('Perlu diperpanjang')
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color('danger'),
            ];
        } catch (Exception $e) {
            return [
                Stat::make('Error', 'Tidak dapat memuat data')
                    ->description('Error: '.$e->getMessage())
                    ->color('gray'),
            ];
        }
    }
}

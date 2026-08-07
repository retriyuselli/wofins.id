<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use App\Support\UserVisibility;
use Carbon\Carbon;
use Exception;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserExpirationWidget extends BaseWidget
{
    // protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        try {
            $base = UserVisibility::constrainUsersQuery(User::query());

            $expiredUsers = (clone $base)->where('expire_date', '<=', Carbon::now())
                ->whereNotNull('expire_date')
                ->count();

            $expiringSoonUsers = (clone $base)->whereBetween('expire_date', [
                Carbon::now(),
                Carbon::now()->addDays(7),
            ])->count();

            $activeUsers = (clone $base)->where(function ($query) {
                $query->whereNull('expire_date')
                    ->orWhere('expire_date', '>', Carbon::now());
            })->count();

            return [
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
            // Fallback jika ada error
            return [
                Stat::make('Status User', 'Error')
                    ->description('Tidak dapat memuat data')
                    ->color('gray'),
            ];
        }
    }
}

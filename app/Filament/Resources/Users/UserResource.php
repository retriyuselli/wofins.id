<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static string|\UnitEnum|null $navigationGroup = 'SDM';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Check if current user is super admin
     */
    public static function isSuperAdmin(): bool
    {
        return UserVisibility::actorIsSuperAdmin();
    }

    /**
     * Check if target user is super admin
     */
    public static function isTargetUserSuperAdmin($record): bool
    {
        if (! $record) {
            return false;
        }

        return $record->hasRole('super_admin');
    }

    /**
     * Apply query restrictions based on user role
     */
    public static function getEloquentQuery(): Builder
    {
        $currentYear = (int) date('Y');

        $query = parent::getEloquentQuery()
            // Payrolls diload sekali untuk semua baris — gunakan $record->payrolls->first() di Table
            ->with(['payrolls' => fn ($q) => $q->latest()])
            ->with('statuses')
            ->with('roles')
            ->withCount('roles')
            // Pre-compute leave aggregates — hindari N+1 di kolom cuti
            ->withSum([
                'leaveRequests as leave_approved_days' => fn ($q) => $q
                    ->where('status', 'approved')
                    ->whereYear('start_date', $currentYear),
            ], 'total_days')
            ->withSum([
                'leaveRequests as leave_pending_days' => fn ($q) => $q
                    ->where('status', 'pending')
                    ->whereYear('start_date', $currentYear),
            ], 'total_days')
            ->withSum([
                'leaveRequests as leave_rejected_days' => fn ($q) => $q
                    ->where('status', 'rejected')
                    ->whereYear('start_date', $currentYear),
            ], 'total_days');

        return UserVisibility::constrainUsersQuery($query);
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            // AccountManagerStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return CompanySubscription::summary(CompanySubscription::RESOURCE_USERS);
    }

    public static function getNavigationBadge(): ?string
    {
        return CompanySubscription::navigationBadge(CompanySubscription::RESOURCE_USERS);
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return CompanySubscription::canCreate(CompanySubscription::RESOURCE_USERS) ? 'primary' : 'warning';
    }
}

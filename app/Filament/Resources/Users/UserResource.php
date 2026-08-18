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

    protected static string|\UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?int $navigationSort = 2;

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
        $query = parent::getEloquentQuery()
            // Payrolls diload sekali untuk semua baris — gunakan $record->payrolls->first() di Table
            ->with(['payrolls' => fn ($q) => $q->latest()])
            ->with('statuses')
            ->with('roles')
            ->withCount('roles');

        return UserVisibility::constrainUsersQuery($query);
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function canCreate(): bool
    {
        return parent::canCreate() && UserVisibility::canCreateTeamUser();
    }

    public static function canEdit($record): bool
    {
        return parent::canEdit($record)
            && UserVisibility::canEditUser($record instanceof User ? $record : null);
    }

    public static function canView($record): bool
    {
        return parent::canView($record)
            && UserVisibility::canAccessUser($record instanceof User ? $record : null);
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

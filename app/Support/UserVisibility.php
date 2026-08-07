<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Isolasi data user:
 * - super_admin: semua
 * - lainnya: tim paket (diri sendiri + user yang created_by = root tim)
 */
class UserVisibility
{
    public static function actorIsSuperAdmin(): bool
    {
        return ProFeatures::actorIsSuperAdmin();
    }

    public static function actorId(): ?int
    {
        $user = Auth::user();

        return $user instanceof User ? (int) $user->id : null;
    }

    /**
     * Root tim paket: pemilik akun (created_by null) atau induk created_by.
     */
    public static function teamRootId(?User $user = null): ?int
    {
        $user ??= Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        if (Schema::hasColumn('users', 'created_by') && $user->created_by) {
            return (int) $user->created_by;
        }

        return (int) $user->id;
    }

    /**
     * Badge kuota: non-SA melihat kuota tim sendiri (bukan agregat platform).
     */
    public static function canViewGlobalUserAggregates(): bool
    {
        return static::actorIsSuperAdmin();
    }

    public static function canViewTeamSeatSummary(): bool
    {
        return static::actorId() !== null;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrainUsersQuery(Builder $query): Builder
    {
        if (static::actorIsSuperAdmin()) {
            return $query;
        }

        $root = static::teamRootId();

        if ($root === null) {
            return $query->whereRaw('1 = 0');
        }

        if (! Schema::hasColumn('users', 'created_by')) {
            return $query->whereKey($root);
        }

        return $query->where(function (Builder $q) use ($root) {
            $q->whereKey($root)->orWhere('created_by', $root);
        });
    }

    /**
     * ID user dalam tim paket (root + anggota).
     *
     * @return list<int>
     */
    public static function teamUserIds(?User $actor = null): array
    {
        $root = static::teamRootId($actor);

        if ($root === null) {
            return [];
        }

        if (! Schema::hasColumn('users', 'created_by')) {
            return [$root];
        }

        return User::query()
            ->where(function (Builder $q) use ($root) {
                $q->whereKey($root)->orWhere('created_by', $root);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Stempel pemilik tim pada create (created_by / user_id).
     * Super admin: biarkan nilai form (boleh null = katalog platform).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function stampTeamOwner(array $data, string $column = 'created_by'): array
    {
        if (static::actorIsSuperAdmin()) {
            return $data;
        }

        $root = static::teamRootId();

        if ($root !== null) {
            $data[$column] = $root;
        }

        return $data;
    }

    /**
     * Agregat global (seluruh company platform): SA + staf Finance / admin_am.
     * Pemilik paket hanya melihat agregat timnya.
     */
    public static function actorSeesGlobalAggregates(): bool
    {
        if (static::actorIsSuperAdmin()) {
            return true;
        }

        $user = Auth::user();

        return $user instanceof User
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['Finance', 'admin_am']);
    }

    /** Key cache agar widget tidak campur angka global vs tim. */
    public static function cacheScopeKey(): string
    {
        if (static::actorSeesGlobalAggregates()) {
            return 'global';
        }

        return 't'.(static::teamRootId() ?? 0);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrainOrdersQuery(Builder $query): Builder
    {
        if (static::actorSeesGlobalAggregates()) {
            return $query;
        }

        return static::constrainOwnedQuery($query, 'user_id');
    }

    /**
     * Expense / DataPembayaran yang terikat order_id ke order tim.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrainViaTeamOrders(Builder $query, string $orderIdColumn = 'order_id'): Builder
    {
        if (static::actorSeesGlobalAggregates()) {
            return $query;
        }

        $teamIds = static::teamUserIds();

        if ($teamIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($orderIdColumn, function ($q) use ($teamIds) {
            $q->select('id')->from('orders')->whereIn('user_id', $teamIds);
        });
    }

    /**
     * ExpenseOps belum punya kolom pemilik — non-SA: kosong (belum bisa diatribusi ke tim).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrainExpenseOpsQuery(Builder $query): Builder
    {
        return static::constrainPlatformOnlyQuery($query);
    }

    /**
     * Data tanpa kolom pemilik tim: hanya SA / staf platform. Non-SA → kosong.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrainPlatformOnlyQuery(Builder $query): Builder
    {
        if (static::actorSeesGlobalAggregates()) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope record yang punya kolom pemilik (user_id / created_by) ke anggota tim.
     * Record dengan pemilik null hanya terlihat super_admin (katalog lama / platform).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrainOwnedQuery(Builder $query, string $column = 'user_id'): Builder
    {
        if (static::actorSeesGlobalAggregates()) {
            return $query;
        }

        $teamIds = static::teamUserIds();

        if ($teamIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $teamIds);
    }

    /**
     * Nota dinas: pengirim / penerima / approver dalam tim.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrainNotaDinasQuery(Builder $query): Builder
    {
        if (static::actorSeesGlobalAggregates()) {
            return $query;
        }

        $teamIds = static::teamUserIds();

        if ($teamIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($teamIds) {
            $q->whereIn('pengirim_id', $teamIds)
                ->orWhereIn('penerima_id', $teamIds)
                ->orWhereIn('approved_by', $teamIds);
        });
    }

    /**
     * Detail ND: lewat order tim, atau (tanpa order) lewat nota dinas tim.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrainNotaDinasDetailsQuery(Builder $query): Builder
    {
        if (static::actorSeesGlobalAggregates()) {
            return $query;
        }

        $teamIds = static::teamUserIds();

        if ($teamIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($teamIds) {
            $q->whereIn('order_id', function ($sub) use ($teamIds) {
                $sub->select('id')->from('orders')->whereIn('user_id', $teamIds);
            })->orWhere(function (Builder $q2) use ($teamIds) {
                $q2->whereNull('order_id')
                    ->whereHas('notaDinas', function (Builder $nd) use ($teamIds) {
                        $nd->where(function (Builder $inner) use ($teamIds) {
                            $inner->whereIn('pengirim_id', $teamIds)
                                ->orWhereIn('penerima_id', $teamIds)
                                ->orWhereIn('approved_by', $teamIds);
                        });
                    });
            });
        });
    }

    /**
     * Pembayaran piutang lewat piutang.dibuat_oleh anggota tim.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrainViaTeamPiutangs(Builder $query, string $piutangIdColumn = 'piutang_id'): Builder
    {
        if (static::actorSeesGlobalAggregates()) {
            return $query;
        }

        $teamIds = static::teamUserIds();

        if ($teamIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($piutangIdColumn, function ($q) use ($teamIds) {
            $q->select('id')->from('piutangs')->whereIn('dibuat_oleh', $teamIds);
        });
    }

    public static function canAccessUser(?User $target): bool
    {
        if (static::actorIsSuperAdmin()) {
            return true;
        }

        if (! $target) {
            return false;
        }

        $root = static::teamRootId();

        if ($root === null) {
            return false;
        }

        if ((int) $target->id === $root) {
            return true;
        }

        return Schema::hasColumn('users', 'created_by')
            && (int) $target->created_by === $root;
    }

    /**
     * Nama role milik pemilik paket (root tim), tanpa super_admin.
     *
     * @return list<string>
     */
    public static function packageOwnerRoleNames(?User $actor = null): array
    {
        $actor ??= Auth::user();

        if (! $actor instanceof User) {
            return ['pengunjung'];
        }

        $rootId = static::teamRootId($actor);
        $owner = $rootId ? User::query()->find($rootId) : $actor;
        $owner ??= $actor;

        $planLike = ['starter', 'professional', 'business', 'enterprise', 'hastana', 'non_hastana', 'lain_lain'];

        $names = $owner->getRoleNames()
            ->reject(function (string $name) use ($planLike): bool {
                $lower = strtolower($name);

                return $lower === 'super_admin' || in_array($lower, $planLike, true);
            })
            ->values()
            ->all();

        return $names !== [] ? $names : ['pengunjung'];
    }

    /**
     * @return list<int>
     */
    public static function packageOwnerRoleIds(?User $actor = null): array
    {
        $names = static::packageOwnerRoleNames($actor);

        return \Spatie\Permission\Models\Role::query()
            ->whereIn('name', $names)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public static function defaultPackageTeamRole(): string
    {
        return static::packageOwnerRoleNames()[0] ?? 'pengunjung';
    }

    /**
     * Nama role yang boleh dipilih actor. null = semua (super_admin).
     * Pemilik paket: hanya role yang sama dengan dirinya.
     *
     * @return list<string>|null
     */
    public static function assignableRoleNames(): ?array
    {
        if (static::actorIsSuperAdmin()) {
            return null;
        }

        return static::packageOwnerRoleNames();
    }

    /**
     * Filter ID role dari form.
     * Non–super_admin: selalu samakan dengan role pemilik paket.
     *
     * @param  list<int|string>|null  $roleIds
     * @return list<int>
     */
    public static function sanitizeAssignableRoleIds(?array $roleIds): array
    {
        if (static::actorIsSuperAdmin()) {
            return collect($roleIds ?? [])
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        // Anggota tim wajib role sama dengan pemilik paket
        $ownerIds = static::packageOwnerRoleIds();

        if ($ownerIds !== []) {
            return $ownerIds;
        }

        $default = \Spatie\Permission\Models\Role::findOrCreate('pengunjung', 'web');

        return [(int) $default->id];
    }

    /**
     * @return callable(Builder): Builder
     */
    public static function usersRelationshipConstraint(): callable
    {
        return static fn (Builder $query): Builder => static::constrainUsersQuery($query);
    }
}

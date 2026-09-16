<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\CompanySubscription;
use Illuminate\Console\Command;

class RevokeInactiveApiTokens extends Command
{
    protected $signature = 'api:revoke-inactive-tokens {--dry-run}';

    protected $description = 'Revoke API tokens belonging to inactive accounts or subscriptions';

    public function handle(): int
    {
        $revoked = 0;

        User::query()
            ->with('company')
            ->whereHas('tokens')
            ->chunkById(200, function ($users) use (&$revoked): void {
                foreach ($users as $user) {
                    $blocked = in_array($user->status, ['inactive', 'terminated'], true)
                        || $user->isExpired()
                        || (! $user->hasRole('super_admin') && (
                            ! $user->company
                            || $user->company->isDeactivated()
                            || CompanySubscription::isExpired($user)
                        ));

                    if (! $blocked) {
                        continue;
                    }

                    $count = $user->tokens()->count();
                    $revoked += $count;
                    if (! $this->option('dry-run')) {
                        $user->tokens()->delete();
                    }
                }
            });

        $this->info(($this->option('dry-run') ? 'Would revoke' : 'Revoked')." {$revoked} token(s).");

        return self::SUCCESS;
    }
}

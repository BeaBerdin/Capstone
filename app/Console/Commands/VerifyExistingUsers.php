<?php

namespace App\Console\Commands;

use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Accounts that already exist count as verified; only users who register
 * from now on must verify their email.
 *
 * Sets email_verified_at to now for users where it is NULL and that were
 * created before the command started (a missing created_at also counts as
 * existing). No other column changes, not even updated_at. Running it again
 * only picks up accounts that are still unverified.
 *
 * Run by hand; it is not a migration, so it never runs automatically.
 */
class VerifyExistingUsers extends Command
{
    protected $signature = 'users:verify-existing
        {--dry-run : Only list the accounts that would be verified; change nothing}';

    protected $description = 'Mark existing accounts with an unverified email as verified';

    public function handle(): int
    {
        $startedAt = Carbon::now();

        $query = fn (): Builder => User::query()
            ->whereNull('email_verified_at')
            ->where(fn (Builder $query) => $query
                ->where('created_at', '<', $startedAt)
                ->orWhereNull('created_at'))
            // Never verify a student applicant whose newest application is
            // pending or rejected; only the EDP's approval allows that.
            ->whereNotIn('id', fn ($sub) => $sub
                ->select('user_id')
                ->from('student_applications')
                ->whereIn('id', fn ($latest) => $latest
                    ->selectRaw('MAX(id)')
                    ->from('student_applications')
                    ->groupBy('user_id'))
                ->whereIn('status', StudentApplication::AWAITING));

        $emails = $query()->orderBy('id')->pluck('email');

        if ($this->option('dry-run')) {
            $this->warn('Dry run: nothing will be changed.');
            $this->line("Accounts that would be marked as verified: {$emails->count()}");

            foreach ($emails as $email) {
                $this->line("  {$email}");
            }

            return self::SUCCESS;
        }

        // toBase() so Eloquent does not also set updated_at.
        $updated = $query()->toBase()->update(['email_verified_at' => $startedAt]);

        $this->info("Accounts marked as verified: {$updated}");

        foreach ($emails as $email) {
            $this->line("  {$email}", null, 'v');
        }

        $this->line('Already verified accounts were left unchanged.');

        return self::SUCCESS;
    }
}

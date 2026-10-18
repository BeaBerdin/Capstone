<?php

namespace App\Console\Commands;

use App\Support\PrivateFiles;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Moves payment receipts, assignment submissions and lesson files that were
 * uploaded before A11 from the public disk to the private disk. Stored paths
 * do not change, so no database update is needed.
 *
 * Each file is copied, the copy is checked (size and SHA-256), and only then
 * is the public copy deleted. A different file already at the same private
 * path is a conflict: it is never overwritten and both copies are kept.
 * Running the command again is safe.
 */
class MakeFilesPrivate extends Command
{
    protected $signature = 'files:make-private
        {--dry-run : Only show what would be done; change nothing}
        {--keep-public : Copy and verify, but keep the public copies}';

    protected $description = 'Move receipts, submissions and lesson files from the public disk to the private disk';

    public function handle(): int
    {
        $public = Storage::disk(PrivateFiles::LEGACY_DISK);
        $private = Storage::disk(PrivateFiles::DISK);

        $dryRun = (bool) $this->option('dry-run');
        $keepPublic = (bool) $this->option('keep-public');

        if ($dryRun) {
            $this->warn('Dry run: nothing will be copied or deleted.');
        }

        $counts = [
            'moved' => 0,
            'copied' => 0,
            'already private' => 0,
            'conflicts' => 0,
            'failed' => 0,
        ];

        foreach (PrivateFiles::DIRECTORIES as $directory) {
            foreach ($public->allFiles($directory) as $path) {
                $result = $this->handleFile($public, $private, $path, $dryRun, $keepPublic);

                $counts[$result]++;
            }
        }

        $this->newLine();
        $this->table(
            ['Result', 'Files'],
            collect($counts)->map(fn ($count, $result) => [$result, $count])->values()->all()
        );

        if ($dryRun) {
            $this->line('Dry run: counts show what would happen.');
        }

        if ($counts['conflicts'] > 0 || $counts['failed'] > 0) {
            $this->error('Some files were not moved. Their public copies were kept; see the messages above.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return 'moved'|'copied'|'already private'|'conflicts'|'failed'
     */
    private function handleFile(
        Filesystem $public,
        Filesystem $private,
        string $path,
        bool $dryRun,
        bool $keepPublic
    ): string {
        $removePublic = ! $dryRun && ! $keepPublic;

        // Already on the private disk (for example after --keep-public).
        if ($private->exists($path)) {
            if (! $this->sameFile($public, $private, $path)) {
                $this->error("Conflict, skipped: {$path} is already on the private disk with different contents.");

                return 'conflicts';
            }

            if ($removePublic) {
                $public->delete($path);
            }

            $this->line("Already private: {$path}", null, 'v');

            return 'already private';
        }

        if ($dryRun) {
            $this->line(($keepPublic ? 'Would copy: ' : 'Would move: ').$path, null, 'v');

            return $keepPublic ? 'copied' : 'moved';
        }

        $stream = $public->readStream($path);

        $written = $stream !== null && $private->writeStream($path, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        if (! $written || ! $this->sameFile($public, $private, $path)) {
            // Remove a partial or wrong copy; the public file is untouched.
            $private->delete($path);

            $this->error("Failed, skipped: {$path} could not be copied and verified.");

            return 'failed';
        }

        if ($keepPublic) {
            $this->line("Copied: {$path}", null, 'v');

            return 'copied';
        }

        $public->delete($path);

        $this->line("Moved: {$path}", null, 'v');

        return 'moved';
    }

    private function sameFile(Filesystem $public, Filesystem $private, string $path): bool
    {
        if ($public->size($path) !== $private->size($path)) {
            return false;
        }

        $publicHash = $this->sha256($public, $path);

        return $publicHash !== null && $publicHash === $this->sha256($private, $path);
    }

    private function sha256(Filesystem $disk, string $path): ?string
    {
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            return null;
        }

        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        fclose($stream);

        return hash_final($context);
    }
}

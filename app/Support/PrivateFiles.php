<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Payment receipts, assignment submissions and lesson files are kept on the
 * private disk (storage/app/private) and served only through
 * PrivateFileController, which checks who may open them.
 *
 * Course thumbnails and profile photos stay on the public disk.
 *
 * Files uploaded before the change are still on the public disk until
 * `php artisan files:make-private` moves them, so reads fall back to the
 * public disk and deletes remove the file from both.
 */
class PrivateFiles
{
    public const DISK = 'local';

    public const LEGACY_DISK = 'public';

    /**
     * Folders (on both disks) that hold private files.
     */
    public const DIRECTORIES = [
        'payment-proofs',
        'assignment-submissions',
        'lesson-files',
    ];

    /**
     * File types the browser may show; everything else is downloaded.
     * SVG is left out on purpose because it can contain scripts.
     */
    public const INLINE_TYPES = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    /**
     * The disk that holds the file: the private disk first, then the public
     * disk for files not moved yet. Null when the file is on neither.
     */
    public static function diskFor(?string $path): ?Filesystem
    {
        if (blank($path)) {
            return null;
        }

        foreach ([self::DISK, self::LEGACY_DISK] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk);
            }
        }

        return null;
    }

    /**
     * Delete the file from both disks.
     */
    public static function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
        Storage::disk(self::LEGACY_DISK)->delete($path);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One sensitive Super Admin action. Entries are written once and can never
 * be edited or deleted through the application; no route changes them.
 * Never pass passwords, tokens or file contents to record().
 *
 * @property int $id
 * @property int|null $actor_id
 * @property int|null $target_user_id
 * @property string $action
 * @property string|null $old_value
 * @property string|null $new_value
 * @property string|null $reason
 * @property string|null $ip_address
 * @property Carbon $created_at
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const ROLE_CHANGED = 'user.role_changed';

    public const PROMOTED_SUPER_ADMIN = 'user.promoted_super_admin';

    public const EMAIL_VERIFIED = 'user.email_verified';

    public const ACTIVATED = 'user.activated';

    public const DEACTIVATED = 'user.deactivated';

    public const DETAILS_UPDATED = 'user.details_updated';

    public const APPLICATION_APPROVED = 'application.approved';

    public const APPLICATION_REJECTED = 'application.rejected';

    public const DEPARTMENT_REMOVED = 'user.department_removed';

    /** Labels for the Audit Log page and its filter. */
    public const ACTIONS = [
        self::ROLE_CHANGED => 'Role changed',
        self::PROMOTED_SUPER_ADMIN => 'Promoted to Super Admin',
        self::EMAIL_VERIFIED => 'Email marked verified',
        self::ACTIVATED => 'Account activated',
        self::DEACTIVATED => 'Account deactivated',
        self::DETAILS_UPDATED => 'Name / email edited',
        self::APPLICATION_APPROVED => 'Application approved',
        self::APPLICATION_REJECTED => 'Application rejected',
        self::DEPARTMENT_REMOVED => 'Removed from department',
    ];

    protected $fillable = [
        'actor_id',
        'target_user_id',
        'action',
        'old_value',
        'new_value',
        'reason',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit log entries cannot be deleted.'));
    }

    /**
     * Write one entry for the signed-in user's action.
     */
    public static function record(
        string $action,
        ?User $target,
        ?string $old = null,
        ?string $new = null,
        ?string $reason = null,
    ): self {
        return self::create([
            'actor_id' => auth()->id(),
            'target_user_id' => $target?->id,
            'action' => $action,
            'old_value' => $old,
            'new_value' => $new,
            'reason' => filled($reason) ? mb_substr(trim($reason), 0, 2000) : null,
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}

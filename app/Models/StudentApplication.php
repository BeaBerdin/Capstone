<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One Study Load submission by a student applicant (first registration or a
 * resubmission). Only the Super Admin (EDP) decides it; see
 * App\Http\Controllers\SuperAdmin\StudentApplicationController.
 *
 * @property int $id
 * @property int $user_id
 * @property int $submission_no
 * @property string $study_load_path
 * @property string $study_load_original_name
 * @property string $study_load_mime
 * @property int $study_load_size
 * @property string $status
 * @property string $applicant_role
 * @property string|null $review_email
 * @property string|null $review_origin
 * @property Carbon $submitted_at
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $rejection_reason
 */
class StudentApplication extends Model
{
    public const STUDENT = 'student';

    public const TEACHER = 'teacher';

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    /** Statuses that keep a student out until the EDP approves. */
    public const AWAITING = [self::PENDING, self::REJECTED];

    /** Study Load rules: PDF, JPG or PNG, up to 10 MB (in kilobytes). */
    public const MAX_KILOBYTES = 10240;

    public const TOO_LARGE_MESSAGE = 'The uploaded file exceeds the 10 MB limit. Please upload a smaller or compressed file.';

    /**
     * Validation rules for a Study Load upload (extension and real MIME type).
     *
     * @return array<int, string>
     */
    public static function studyLoadRules(): array
    {
        return [
            'required',
            'file',
            'mimes:pdf,jpg,jpeg,png',
            'mimetypes:application/pdf,image/jpeg,image/png',
            'max:'.self::MAX_KILOBYTES,
        ];
    }

    /**
     * @return array<string, string>
     */
    /**
     * Validation rules for a teacher's Teacher ID upload: the same limits and
     * checks as a Study Load (PDF, JPG or PNG, extension and real MIME type,
     * up to 10 MB).
     *
     * @return array<int, string>
     */
    public static function teacherIdRules(): array
    {
        return self::studyLoadRules();
    }

    /**
     * @return array<string, string>
     */
    public static function teacherIdMessages(string $field = 'teacher_id'): array
    {
        return [
            "{$field}.required" => 'Please upload your Teacher ID (faculty or employee ID): PDF, JPG or PNG.',
            "{$field}.file" => 'The Teacher ID must be a PDF, JPG or PNG file.',
            "{$field}.uploaded" => self::TOO_LARGE_MESSAGE,
            "{$field}.max" => self::TOO_LARGE_MESSAGE,
            "{$field}.mimes" => 'The Teacher ID must be a PDF, JPG or PNG file.',
            "{$field}.mimetypes" => 'The Teacher ID must be a PDF, JPG or PNG file.',
        ];
    }

    public static function studyLoadMessages(string $field = 'study_load'): array
    {
        return [
            "{$field}.required" => 'Please upload your Study Load (PDF, JPG or PNG).',
            "{$field}.file" => 'The Study Load must be a PDF, JPG or PNG file.',
            "{$field}.uploaded" => self::TOO_LARGE_MESSAGE,
            "{$field}.max" => self::TOO_LARGE_MESSAGE,
            "{$field}.mimes" => 'The Study Load must be a PDF, JPG or PNG file.',
            "{$field}.mimetypes" => 'The Study Load must be a PDF, JPG or PNG file.',
        ];
    }

    protected $fillable = [
        'user_id',
        'submission_no',
        'study_load_path',
        'study_load_original_name',
        'study_load_mime',
        'study_load_size',
        'status',
        'submitted_at',
        'applicant_role',
        'review_email',
        'review_origin',
    ];

    protected function casts(): array
    {
        return [
            'submission_no' => 'integer',
            'study_load_size' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    /**
     * Teacher reviews reuse this table without a Study Load.
     */
    public function hasStudyLoad(): bool
    {
        return filled($this->study_load_path);
    }

    /** True when the applicant registered as a teacher. */
    public function isTeacherApplication(): bool
    {
        return $this->applicant_role === self::TEACHER;
    }

    public function reviewsEmail(string $email): bool
    {
        return $this->review_email !== null && hash_equals($this->review_email, $email);
    }

    /** "Teacher" or "Student", for labels. */
    public function applicantRoleLabel(): string
    {
        return $this->isTeacherApplication() ? 'Teacher' : 'Student';
    }

    /**
     * Call inside a transaction that creates or locks the user row.
     *
     * $teacherId holds the stored Teacher ID's columns (see
     * StudyLoads::storeTeacherId). A teacher's ID uses the same file columns
     * as a student's Study Load; the row is told apart by applicant_role.
     * Reviews started by the EDP (account creation, role or email change)
     * have no file.
     *
     * @param  array{study_load_path?: string, study_load_original_name?: string, study_load_mime?: string, study_load_size?: int}  $teacherId
     */
    public static function submitForTeacher(User $user, string $origin, array $teacherId = []): self
    {
        return self::create(array_merge([
            'user_id' => $user->id,
            'submission_no' => ((int) self::where('user_id', $user->id)->max('submission_no')) + 1,
            'applicant_role' => self::TEACHER,
            'review_email' => $user->email,
            'review_origin' => $origin,
            'study_load_path' => '',
            'study_load_original_name' => '',
            'study_load_mime' => '',
            'study_load_size' => 0,
            'status' => self::PENDING,
            'submitted_at' => now(),
        ], array_intersect_key($teacherId, array_flip([
            'study_load_path',
            'study_load_original_name',
            'study_load_mime',
            'study_load_size',
        ]))));
    }

    /** Label of the uploaded file: "Teacher ID" or "Study Load". */
    public function documentLabel(): string
    {
        return $this->isTeacherApplication() ? 'Teacher ID' : 'Study Load';
    }

    /**
     * Only applications of accounts with the given role ("student" or
     * "teacher"); any other value leaves the query unchanged.
     *
     * @param  Builder<StudentApplication>  $query
     */
    public function scopeForApplicantRole(Builder $query, ?string $role): void
    {
        if (in_array($role, ['student', 'teacher'], true)) {
            $query->where('applicant_role', $role);
        }
    }

    /**
     * Only the newest submission per user and application role.
     *
     * @param  Builder<StudentApplication>  $query
     */
    public function scopeLatestPerStudent(Builder $query): void
    {
        $query->whereIn('id', fn ($sub) => $sub
            ->selectRaw('MAX(id)')
            ->from('student_applications')
            ->groupBy('user_id', 'applicant_role'));
    }

    public function sizeForHumans(): string
    {
        $kb = $this->study_load_size / 1024;

        return $kb >= 1024
            ? number_format($kb / 1024, 1).' MB'
            : number_format(max($kb, 1), 0).' KB';
    }
}

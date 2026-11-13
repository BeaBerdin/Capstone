<?php

namespace App\Services;

use App\Exceptions\DeletionBlockedException;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseCategory;
use Closure;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Retention checks deliberately query the database, not loaded relationships.
 * All statuses count as history, including cancelled, rejected and revoked.
 * SQL cascades, bulk deletes and suppressed model events require separate
 * database-level protection; they do not invoke the model deletion guards.
 */
class RecordRetentionService
{
    public function assignmentDeletionBlockReason(Assignment $assignment): ?string
    {
        if ($assignment->getConnection()->table('submissions')
            ->where('assignment_id', $assignment->getKey())->exists()) {
            return 'This assignment cannot be deleted because student submissions exist. All answers and grades have been preserved.';
        }

        return null;
    }

    public function courseDeletionBlockReason(Course $course): ?string
    {
        $connection = $course->getConnection();

        foreach ([
            'enrollments' => 'enrollment',
            'transactions' => 'transaction',
            'certificates' => 'certificate',
            'course_reviews' => 'review',
            'course_invitations' => 'invitation',
            'student_removal_requests' => 'student removal request',
            'ai_recommendations' => 'recommendation',
            'learning_path_courses' => 'learning path',
        ] as $table => $label) {
            if ($connection->table($table)->where('course_id', $course->getKey())->exists()) {
                return "This course cannot be deleted because it has {$label} history.";
            }
        }

        // These records must protect a course even if no enrollment remains.
        foreach ([
            ['submissions', 'assignments', 'assignment_id', 'student submission'],
            ['quiz_results', 'quizzes', 'quiz_id', 'quiz result'],
            ['lesson_progress', 'lessons', 'lesson_id', 'lesson progress'],
        ] as [$history, $parent, $foreignKey, $label]) {
            if ($connection->table($history)
                ->join($parent, "{$parent}.id", '=', "{$history}.{$foreignKey}")
                ->where("{$parent}.course_id", $course->getKey())->exists()) {
                return "This course cannot be deleted because it has {$label} history.";
            }
        }

        return null;
    }

    public function categoryDeletionBlockReason(CourseCategory $category): ?string
    {
        if ($category->getConnection()->table('courses')
            ->where('category_id', $category->getKey())->exists()) {
            return 'This category cannot be deleted because it contains courses. Its courses and their records have been preserved.';
        }

        return null;
    }

    /** @param (Closure(Assignment): void)|null $authorize */
    public function deleteAssignment(Assignment $assignment, ?Closure $authorize = null): void
    {
        $assignment->getConnection()->transaction(function () use ($assignment, $authorize): void {
            // Use the same parent-before-child order as course deletion.
            Course::on($assignment->getConnectionName())->whereKey($assignment->course_id)
                ->lockForUpdate()->firstOrFail();

            $locked = Assignment::on($assignment->getConnectionName())->whereKey($assignment->getKey())
                ->lockForUpdate()->firstOrFail();

            if ((int) $locked->course_id !== (int) $assignment->course_id) {
                throw new DeletionBlockedException('This assignment has changed. Refresh the page before trying again.');
            }

            if ($authorize !== null) {
                $authorize($locked);
            }

            if (($reason = $this->assignmentDeletionBlockReason($locked)) !== null) {
                throw new DeletionBlockedException($reason);
            }

            $locked->delete();
        });
    }

    /** @param (Closure(Course): void)|null $authorize */
    public function deleteCourse(Course $course, ?Closure $authorize = null): void
    {
        $connection = $course->getConnection();

        $connection->transaction(function () use ($course, $connection, $authorize): void {
            $locked = Course::on($course->getConnectionName())->whereKey($course->getKey())
                ->lockForUpdate()->firstOrFail();

            if ($authorize !== null) {
                $authorize($locked);
            }

            // Lock descendant parents referenced by new submissions, attempts
            // and progress rows before inspecting history or allowing cascades.
            foreach (['lessons', 'assignments', 'quizzes'] as $table) {
                $connection->table($table)->where('course_id', $locked->getKey())
                    ->orderBy('id')->lockForUpdate()->get(['id']);
            }

            if (($reason = $this->courseDeletionBlockReason($locked)) !== null) {
                throw new DeletionBlockedException($reason);
            }

            $thumbnail = $locked->getAttribute('thumbnail');
            $locked->delete();

            if (is_string($thumbnail) && $thumbnail !== '') {
                $connectionName = $locked->getConnectionName();

                // A blocked or rolled-back deletion must never remove a file.
                $connection->afterCommit(function () use ($thumbnail, $connectionName): void {
                    try {
                        if (! Course::on($connectionName)->where('thumbnail', $thumbnail)->exists()) {
                            Storage::disk('public')->delete($thumbnail);
                        }
                    } catch (Throwable $exception) {
                        // Retaining an orphaned thumbnail is safer than undoing
                        // a committed action or deleting a referenced upload.
                        report($exception);
                    }
                });
            }
        });
    }

    public function deleteCategory(CourseCategory $category): void
    {
        $category->getConnection()->transaction(function () use ($category): void {
            $locked = CourseCategory::on($category->getConnectionName())->whereKey($category->getKey())
                ->lockForUpdate()->firstOrFail();

            if (($reason = $this->categoryDeletionBlockReason($locked)) !== null) {
                throw new DeletionBlockedException($reason);
            }

            $locked->delete();
        });
    }
}

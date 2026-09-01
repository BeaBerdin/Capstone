<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningPath extends Model
{
    protected $fillable = [
        'name',
        'description',
        'student_id',
        'difficulty_level',
        'is_generated',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'learning_path_courses')
            ->withPivot('course_order')
            ->orderBy('learning_path_courses.course_order');
    }
}
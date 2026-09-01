<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'teacher_id',
        'category_id',
        'title',
        'description',
        'thumbnail',
        'intro_video',
        'difficulty_level',
        'price',
        'status',
        'estimated_hours',
        'certificate_available',
    ];

    public function category()
 {
    return $this->belongsTo(CourseCategory::class, 'category_id');
 }

   public function teacher()
 {
    return $this->belongsTo(User::class, 'teacher_id');
 }
   public function lessons()
 {
    return $this->hasMany(Lesson::class);
 }

   public function quizzes()
 {
    return $this->hasMany(Quiz::class);
 }

 public function enrollments()
{
    return $this->hasMany(Enrollment::class);
}
 public function learningPaths()
{
    return $this->belongsToMany(
        LearningPath::class,
        'learning_path_courses'
    );
}

}
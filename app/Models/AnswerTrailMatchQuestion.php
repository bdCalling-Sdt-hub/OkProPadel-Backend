<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerTrailMatchQuestion extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "question_id",
        "answer",
        "trail_match_id",
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function trailMatchQuestion()
    {
        return $this->belongsTo(TrailMatchQuestion::class,"trail_match_question_id","id");
    }
    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}

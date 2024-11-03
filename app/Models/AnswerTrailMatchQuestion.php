<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerTrailMatchQuestion extends Model
{
    use HasFactory;
    protected $fillable = [
        "trail_match_id",
        "user_id",
        "trail_match_question_id",
        "answer",
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

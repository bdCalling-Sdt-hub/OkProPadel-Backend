<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfterMatchQuestionAnswer extends Model
{
    use HasFactory;
    protected $fillable = [
        "questionnaire_id",
        "answer",
        "match_id",
        "user_id",
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function match()
    {
        return $this->belongsTo(PadelMatch::class, 'match_id'); // Adjust 'match_id' if your foreign key is named differently
    }
    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class, 'questionnaire_id');
    }

}

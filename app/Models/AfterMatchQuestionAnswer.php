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
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrailMatchQuestion extends Model
{
    use HasFactory;
    protected $fillable = [
        "question",
        "question_2",
        "options",
        "options_2",
        "status",
        "question_es"
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrailMatchQuestion extends Model
{
    use HasFactory;
    protected $fillable = [
        "question",
        "options",
        "status",
        "question_es"
    ];
}

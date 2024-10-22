<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'questionnaire_id', 'response','match_id'];

    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Questionnaire extends Model
{
    use HasFactory;
    protected $fillable = ['question', 'type', 'options'];

    protected $casts = [
        'options' => 'json',
    ];

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrailMatch extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'volunteer_id', 'time', 'date','club_id','status','request_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class, 'volunteer_id');
    }
    public function club()
    {
        return $this->belongsTo(Club::class,'club_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PadelMatchMemberHistory extends Model
{
    use HasFactory;
    protected $fillable = ['padel_match_id', 'user_id'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function padelMatch()
    {
        return $this->belongsTo(PadelMatch::class, 'padel_match_id');
    }
}

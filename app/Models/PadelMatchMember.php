<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PadelMatchMember extends Model
{
    use HasFactory;
    protected $fillable = [
        'padel_match_id',
        'user_id',
        'isApproved',
    ];
    public function padelMatch()
    {
        return $this->belongsTo(PadelMatch::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


}

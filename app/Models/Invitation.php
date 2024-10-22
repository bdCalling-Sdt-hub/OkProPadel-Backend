<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_id',
        'invited_user_id',
    ];
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    public function invitedUser()
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }
}

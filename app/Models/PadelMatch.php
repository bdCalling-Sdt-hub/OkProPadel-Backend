<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PadelMatch extends Model
{
    use HasFactory;
    protected $fillable = [
        'latitude',
        'longitude',
        'mind_text',
        'level',
        'level_name',
        'selected_level',
        'creator_id',
    ];
    public function members()
    {
        return $this->belongsToMany(User::class, 'padel_match_members');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'padel_match_user', 'padel_match_id', 'user_id');
    }
    public function memberHistories()
    {
        return $this->hasMany(PadelMatchMemberHistory::class, 'padel_match_id');
    }

    public function group()
    {
        $this->belongsTo(Group::class);
    }

}

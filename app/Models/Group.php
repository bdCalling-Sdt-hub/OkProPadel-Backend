<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    use HasFactory;
    protected $fillable = [
        "name",
        "match_id",
        "creator_id",
        "image",
    ];
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members');
    }
    public function messages()
    {
        return $this->hasMany(GroupMessage::class, 'group_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

}

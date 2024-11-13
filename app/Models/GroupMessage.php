<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    use HasFactory;
    protected $fillable = ['group_id', 'user_id', 'message', 'images'];

    protected $casts = [
        'images' => 'array',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'group_message_user')
                    ->withPivot('is_read')
                    ->withTimestamps();
    }

}

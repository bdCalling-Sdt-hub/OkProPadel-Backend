<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessageUser extends Model
{
    use HasFactory;
    protected $fillable =[
        "group_message_id",
        "user_id",
        "is_read"
    ];

}

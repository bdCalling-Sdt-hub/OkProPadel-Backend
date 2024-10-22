<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Volunteer extends Model
{

    use HasFactory,Notifiable;
    protected $fillable = [
        "name",
        "email",
        "location",
        "level",
        "role",
        "image",
        "status",
        "phone_number",
    ];
    public function volunter()
    {
        return $this->belongsTo(Volunteer::class);
    }
}

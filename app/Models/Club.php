<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    use HasFactory;
    protected $fillable = [
        "banners",
        "description",
        "activities",
        "club_name",
        "location",
        "website",
        "status",
        "latitude",
        "longitude",
    ];
}

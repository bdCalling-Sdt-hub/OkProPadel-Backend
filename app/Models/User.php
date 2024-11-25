<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable,HasApiTokens;

    protected $fillable = [
        'full_name',
        'user_name',
        'email',
        'password',
        'address',
        'verify_email',
        'verification_token',
        'otp',
        'otp_expires_at',
        'otp_verified_at',
        'image',
        'role',
        'google_id',
        'facebook_id',
        'latitude',
        'longitude',
        'language',
        'status',
        'age',
        'gender',
        'side_of_the_court',
        'matches_played',
        'level',
        'level_name',
        'points',
        'location',
        'mute_notifications',
        'adjust_status'
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function sentMessages()
    {
        return $this->hasMany(GroupMessage::class, 'user_id');
    }
    public function createdGroups()
    {
        return $this->hasMany(Group::class, 'creator_id');
    }
    public function groupMessages()
    {
        return $this->belongsToMany(GroupMessage::class, 'group_message_user')
                    ->withPivot('is_read')
                    ->withTimestamps();
    }
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members', 'user_id', 'group_id');
    }


}

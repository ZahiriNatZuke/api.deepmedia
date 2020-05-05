<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fullname', 'username', 'email', 'password', 'ip_list'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'ip_list' => 'json'
    ];

    public function channel()
    {
        return $this->hasOne(Channel::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($user) {
            $user->channel()->create();
        });
    }

    public function myLikes()
    {
        return $this->belongsToMany(Video::class);
    }

    public function myFavorites()
    {
        return $this->belongsToMany(Video::class);
    }

}

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
        'fullname', 'username', 'email', 'password'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        static::created(function ($user) {
            $user->channel()->create();
            $user->record()->create([
                'banished' => [
                    'status' => false,
                    'why' => '',
                    'byWho' => '',
                    'banish_expired_at' => ''
                ],
                'ip_list' => [],
                'reset_password' => [
                    'secret_list' => '',
                    'password' => '',
                    'password_expired_at' => ''
                ]
            ]);
        });
    }

    public function channel()
    {
        return $this->hasOne(Channel::class);
    }

    public function record()
    {
        return $this->hasOne(Record::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function myLikes()
    {
        return $this->belongsToMany(Video::class);
    }

    public function bugs()
    {
        return $this->hasMany(Bug::class);
    }

    public function suggestions()
    {
        return $this->hasMany(Suggestion::class);
    }

}

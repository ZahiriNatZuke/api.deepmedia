<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Video extends Model
{
    use Notifiable;

    protected $table = 'videos';
    protected $primaryKey = 'id';
    protected $attributes = [
        'title',
        'description',
        'state',
        'category',
        'poster',
        'video',
        'views_count',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'description', 'state', 'category', 'poster', 'video', 'views_count'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        //
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

    public function poster_path()
    {
        return '/storage/video-' . $this->channel->user->id . '/poster/' . $this->poster;
    }

    public function video_path()
    {
        return '/storage/video-' . $this->channel->user->id . '/video/' . $this->poster;
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'DESC');
    }

    public function countLikes()
    {
        return $this->belongsToMany(User::class);
    }

    public function favoriteForWho()
    {
        return $this->belongsToMany(User::class);
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Video extends Model
{
    use Notifiable;

    protected $table = 'videos';
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'description', 'state', 'category', 'poster', 'video', 'views_count', 'duration'
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

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [
        'comments', 'favoriteForWho', 'Likes', 'channel'
    ];

    /**
     * The relationship counts that should be eager loaded on every query.
     *
     * @var array
     */
    protected $withCount = [
        'comments', 'Likes'
    ];

    /**
     * Get the path for poster.
     *
     * @param string $value
     * @return array
     */
    public function getPosterAttribute($value)
    {
        $path = '/uploads/channel-' . $this->channel_id . '/video-' . $this->id . '/poster/';
        return array(
            'name' => $value,
            'path' => $path . $value
        );
    }

    /**
     * Get the path for video.
     *
     * @param string $value
     * @return array
     */
    public function getVideoAttribute($value)
    {
        $path = '/uploads/channel-' . $this->channel_id . '/video-' . $this->id . '/video/';
        return array(
            'name' => $value,
            'path' => $path . $value
        );
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderByDesc('created_at');
    }

    public function Likes()
    {
        return $this->belongsToMany(User::class);
    }

    public function favoriteForWho()
    {
        return $this->belongsToMany(Channel::class);
    }

}

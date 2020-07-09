<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Channel_Video extends Model
{
    use Notifiable;

    protected $table = 'channel_video';
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'channel_id', 'video_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id'
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [
        'channel', 'video'
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

}

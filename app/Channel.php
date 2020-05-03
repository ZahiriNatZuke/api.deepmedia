<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Channel extends Model
{
    use Notifiable;

    protected $table = 'channels';
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'avatar'
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

    public function avatar_path()
    {
        return '/storage/channel-' . $this->user->id . '/' . $this->avatar;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function countVideo()
    {
        return $this->videos()->count();
    }

    public function countLikes()
    {
        //
    }

    public function countViews()
    {
        //
    }

    public function countComments()
    {
        //
    }

    public function videos()
    {
        return $this->hasMany(Video::class)->orderByDesc('created_at');
    }

}

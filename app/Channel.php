<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;

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
        'avatar', 'user_id'
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
        'user'
    ];

    /**
     * The relationship counts that should be eager loaded on every query.
     *
     * @var array
     */
    protected $withCount = [
        'videos'
    ];

    /**
     * Get the path for avatar.
     *
     * @param string $value
     * @return array
     */
    public function getAvatarAttribute($value)
    {
        if ($value != 'wXgHlUZxP82XAx5MWiEUhLP6DWdoZg956HH8gvbJ.png') {
            $path = '/uploads/channel-' . $this->id . '/avatar/';
            return array(
                'name' => $value,
                'path' => $path . $value
            );
        }
        return array(
            'name' => $value,
            'path' => '/' . $value
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function videos()
    {
        return $this->hasMany(Video::class)->orderByDesc('created_at');
    }

}

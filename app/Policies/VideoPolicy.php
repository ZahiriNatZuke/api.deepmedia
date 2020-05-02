<?php

namespace App\Policies;

use App\User;
use App\Video;
use Illuminate\Auth\Access\HandlesAuthorization;

class VideoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any videos.
     *
     * @param User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the video.
     *
     * @param User $user
     * @param Video $video
     * @return mixed
     */
    public function view(User $user, Video $video)
    {
        //
    }

    /**
     * Determine whether the user can create videos.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the video.
     *
     * @param User $user
     * @param Video $video
     * @return mixed
     */
    public function update(User $user, Video $video)
    {
        //
    }

    /**
     * Determine whether the user can delete the video.
     *
     * @param User $user
     * @param Video $video
     * @return mixed
     */
    public function delete(User $user, Video $video)
    {
        //
    }

    /**
     * Determine whether the user can restore the video.
     *
     * @param User $user
     * @param Video $video
     * @return mixed
     */
    public function restore(User $user, Video $video)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the video.
     *
     * @param User $user
     * @param Video $video
     * @return mixed
     */
    public function forceDelete(User $user, Video $video)
    {
        //
    }
}

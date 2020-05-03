<?php

/** @var Factory $factory */

use App\Comment;
use App\User;
use App\Video;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

$factory->define(Comment::class, function (Faker $faker) {
    return [
        'body' => $faker->text(100),
        'user_id' => $faker->randomElement(User::query()->get(['id'])),
        'video_id' => $faker->randomElement(Video::query()->get(['id']))
    ];
});

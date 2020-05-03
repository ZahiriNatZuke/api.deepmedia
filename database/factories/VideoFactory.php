<?php

/** @var Factory $factory */

use App\Channel;
use App\Video;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

$factory->define(video::class, function (Faker $faker) {
    return [
        'title' => $faker->text(10),
        'description' => $faker->text(30),
        'state' => $faker->randomElement(['Public', 'Private']),
        'category' => $faker->randomElement(['Gameplay', 'Musical', 'Joke', 'Interesting', 'Tech', 'Tutorial']),
        'poster' => $faker->imageUrl(),
        'video' => $faker->imageUrl(),
        'views_count' => $faker->numberBetween(0, 1000),
        'channel_id' => $faker->randomElement(Channel::query()->get(['id']))
    ];
});

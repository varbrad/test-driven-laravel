<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Concert;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(Concert::class, function (Faker $faker) {
    return [
        'title' => 'Test band',
        'subtitle' => 'with other bands',
        'date' => Carbon::parse('+1 week'),
        'ticket_price' => 1999,
        'venue' => 'Test Venue',
        'venue_address' => '42b Somewhere Lane',
        'city' => 'Laratown',
        'state' => 'CK',
        'zip' => '12345',
        'additional_information' => 'Some sample text'
    ];
});

$factory->state(Concert::class, 'published', function (Faker $faker) {
    return [
        'published_at' => Carbon::parse('-1 week'),
    ];
});

$factory->state(Concert::class, 'unpublished', function (Faker $faker) {
    return [
        'published_at' => null,
    ];
});

<?php

return [
    'name' => 'Hotel TV',
    'location' => 'Zanzibar',
    'reception' => '709',
    'breakfast' => '07:00 - 10:00',
    'note' => 'Enjoy your stay.',
    'contacts' => [
        ['name' => 'Reception', 'ext' => '709'],
        ['name' => 'Reception 2', 'ext' => '710'],
        ['name' => 'Gold Dhow Bar', 'ext' => '747'],
        ['name' => 'Sultan Bar', 'ext' => '734'],
        ['name' => 'Kilimanjaro Restaurant', 'ext' => '730'],
    ],
    'menu' => [
        ['name' => 'Breakfast', 'hours' => '07:00 - 10:00', 'items' => ['Coffee and tea', 'Fruit', 'Eggs', 'Bread']],
        ['name' => 'Lunch', 'hours' => '12:30 - 15:00', 'items' => ['Grills', 'Rice', 'Salads']],
        ['name' => 'Dinner', 'hours' => '19:00 - 22:00', 'items' => ['Seafood', 'Swahili dishes', 'Desserts']],
    ],
    'links' => [
        ['name' => 'TV Garden', 'url' => 'https://tv.garden/'],
        ['name' => 'YouTube', 'url' => 'https://www.youtube.com'],
        ['name' => 'Netflix', 'url' => 'https://www.netflix.com'],
    ],
];

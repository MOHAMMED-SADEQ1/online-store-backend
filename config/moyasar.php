<?php

return [
    'secret_key'     => env('MOYASAR_SECRET_KEY'),
    'publishable_key' => env('MOYASAR_PUBLISHABLE_KEY'),
    'webhook_secret'  => env('MOYASAR_WEBHOOK_SECRET'),
    'base_url'        => 'https://api.moyasar.com/v1',
    'test_mode'       => env('MOYASAR_TEST_MODE', true),
];

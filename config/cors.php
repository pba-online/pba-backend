<?php

$frontendOrigins = array_filter(array_map(
    'trim',
    explode(',', (string) env('FRONTEND_URL', 'http://localhost:5173'))
));
$originPatterns = array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGIN_PATTERNS', ''))
));

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique($frontendOrigins)),

    'allowed_origins_patterns' => array_values($originPatterns),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];

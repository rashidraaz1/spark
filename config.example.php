<?php

// Copy this file to config.php and update with your actual Cloudflare credentials

return [
    'cloudflare' => [
        'email' => 'your-email@example.com',
        'api_key' => 'your-global-api-key-here',
        // OR use API token instead of email + api_key (recommended)
        'api_token' => 'your-api-token-here'
    ]
];

?>
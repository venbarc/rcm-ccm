<?php

return [
    'oneaccess_url' => env('ONEACCESS_AUTH_URL'),
    'api_key' => env('ONEACCESS_API_KEY'),
    'bootstrap_admin_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('ONEACCESS_ADMIN_EMAILS', '')),
    ))),
];

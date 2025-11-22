<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create or find test user
$user = \App\Models\User::firstOrCreate(
    ['email' => 'test@example.com'],
    [
        'name' => 'Test User',
        'password' => bcrypt('password'),
    ]
);

// Generate fresh token
$user->tokens()->delete(); // Delete old tokens
$token = $user->createToken('api-test-token')->plainTextToken;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           API TEST AUTHENTICATION CREDENTIALS                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Email:    test@example.com\n";
echo "Password: password\n";
echo "\n";
echo "Bearer Token:\n";
echo $token . "\n";
echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                   HOW TO USE IN SWAGGER                      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "1. Visit: http://tripplanner.test/api/documentation\n";
echo "2. Click the 'Authorize' button (top right)\n";
echo "3. Enter: Bearer " . $token . "\n";
echo "4. Click 'Authorize' then 'Close'\n";
echo "5. Try any protected endpoint!\n";
echo "\n";

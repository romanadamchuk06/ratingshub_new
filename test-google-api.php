<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$platform = \App\Models\ConnectedPlatform::where('provider', 'google')->first();

if (!$platform) {
    echo "Keine Plattform verbunden!\n";
    exit;
}

$token = $platform->access_token;

echo "=== GOOGLE API RAW RESPONSES ===\n\n";

// 1. Accounts API
echo "1. ACCOUNTS API\n";
echo "URL: https://mybusinessaccountmanagement.googleapis.com/v1/accounts\n\n";

$accountsResponse = \Illuminate\Support\Facades\Http::withToken($token)
    ->get('https://mybusinessaccountmanagement.googleapis.com/v1/accounts');

echo "Status: " . $accountsResponse->status() . "\n";
echo "JSON:\n";
echo json_encode($accountsResponse->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

// 2. Locations API
if ($accountsResponse->successful() && isset($accountsResponse->json()['accounts'][0])) {
    $accountName = $accountsResponse->json()['accounts'][0]['name'];

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "2. LOCATIONS API\n";
    $locUrl = "https://mybusinessbusinessinformation.googleapis.com/v1/{$accountName}/locations";
    echo "URL: {$locUrl}?readMask=name,title,storefrontAddress&pageSize=100\n\n";

    $locationsResponse = \Illuminate\Support\Facades\Http::withToken($token)
        ->get($locUrl, [
            'readMask' => 'name,title,storefrontAddress',
            'pageSize' => 100
        ]);

    echo "Status: " . $locationsResponse->status() . "\n";
    echo "JSON:\n";
    echo json_encode($locationsResponse->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}

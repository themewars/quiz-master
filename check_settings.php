<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Setting;

echo "=== Settings Database Check ===\n";
echo "Settings count: " . Setting::count() . "\n\n";

if (Setting::count() > 0) {
    $setting = Setting::first();
    echo "=== First Setting Record ===\n";
    echo "ID: " . $setting->id . "\n";
    echo "App Name: " . ($setting->app_name ?? 'NULL') . "\n";
    echo "Hero Title: " . ($setting->hero_title ?? 'NULL') . "\n";
    echo "Hero Sub Title: " . ($setting->hero_sub_title ?? 'NULL') . "\n";
    echo "Hero Description: " . ($setting->hero_description ?? 'NULL') . "\n";
    echo "Email: " . ($setting->email ?? 'NULL') . "\n";
    echo "Contact: " . ($setting->contact ?? 'NULL') . "\n";
    echo "Created At: " . $setting->created_at . "\n";
    echo "Updated At: " . $setting->updated_at . "\n";
} else {
    echo "No settings found in database!\n";
}

echo "\n=== All Settings Columns ===\n";
$columns = \DB::select("SHOW COLUMNS FROM settings");
foreach ($columns as $column) {
    echo $column->Field . " (" . $column->Type . ")\n";
}

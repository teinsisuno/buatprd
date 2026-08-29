<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
config(['database.default' => 'sqlite']);
config(['database.connections.sqlite.database' => ':memory:']);
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo "tiers: ".\App\Models\MembershipTier::count()."\n";
echo "roles: ".Spatie\Permission\Models\Role::count()."\n";
try {
    $user = App\Models\User::create(['name'=>'Test','email'=>'test2@example.com','password'=>bcrypt('password')]);
    echo "user created\n";
    $user->assignRole('member');
    echo "role assigned\n";
} catch (Exception $e) {
    echo "ERROR: ".$e->getMessage()."\n";
    echo substr($e->getTraceAsString(),0,2000);
}

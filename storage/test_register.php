<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MembershipTier;
use Spatie\Permission\Models\Role;

Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--database' => 'sqlite', '--force' => true]);
echo "migrated\n";
echo "tiers: ".MembershipTier::count()."\n";
echo "roles: ".Role::count()."\n";

try {
    $user = App\Models\User::create(['name'=>'Test','email'=>'test2@example.com','password'=>bcrypt('password')]);
    echo "user created: {$user->id}\n";
    $user->assignRole('member');
    echo "role assigned\n";
} catch (Exception $e) {
    echo "ERROR: ".$e->getMessage()."\n";
    echo $e->getTraceAsString();
}

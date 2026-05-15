<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Staff;
use Illuminate\Support\Facades\DB;

$role = DB::table('roles')->where('role_name', 'Compliance Officer')->first();
if ($role) {
    Staff::updateOrCreate(
        ['email' => 'compliance@bank.local'],
        [
            'full_name' => 'Compliance Reviewer',
            'password_hash' => bcrypt('password'),
            'role_id' => $role->id,
            'branch_id' => 1,
            'status' => 'active'
        ]
    );
    echo "OK: compliance@bank.local created\n";
} else {
    echo "ERROR: Role 'Compliance Officer' not found\n";
}

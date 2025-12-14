<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

$user = User::where('username', 'testuser')->first();
if (!$user) {
    echo "User not found\n";
    exit;
}

echo "User ID: {$user->id}\n";
echo "Username: {$user->username}\n";
echo "Group ID: {$user->group_id}\n";

$group = $user->group;
if (!$group) {
    echo "Group not found\n";
    exit;
}

echo "\n=== Group Info ===\n";
echo "Group ID: {$group->id}\n";
echo "Group Name: {$group->name}\n";
echo "Master User ID: {$group->master_user_id}\n";

echo "\n=== Stripe Subscriptions ===\n";
$subscriptions = $group->subscriptions()->get();
echo "Subscription count: {$subscriptions->count()}\n";

if ($subscriptions->count() === 0) {
    echo "No subscriptions found\n";
} else {
    foreach ($subscriptions as $sub) {
        echo "\n--- Subscription #{$sub->id} ---\n";
        echo "Stripe ID: {$sub->stripe_id}\n";
        echo "Stripe Status: {$sub->stripe_status}\n";
        echo "Stripe Price: {$sub->stripe_price}\n";
        echo "Quantity: {$sub->quantity}\n";
        echo "Trial Ends At: " . ($sub->trial_ends_at ?? 'null') . "\n";
        echo "Ends At: " . ($sub->ends_at ?? 'null') . "\n";
        echo "Created At: {$sub->created_at}\n";
        echo "Updated At: {$sub->updated_at}\n";
    }
}

echo "\n=== Checking Stripe Active Subscriptions ===\n";
if ($group->subscribed()) {
    echo "✅ Group has active subscription\n";
    
    $subscription = $group->subscription();
    if ($subscription) {
        echo "Active subscription stripe_status: {$subscription->stripe_status}\n";
        echo "Active subscription stripe_price: {$subscription->stripe_price}\n";
    }
} else {
    echo "❌ Group does NOT have active subscription\n";
}

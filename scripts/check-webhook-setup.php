<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Webhook Setup Check ===\n\n";

// 1. Token Packages
echo "Active Token Packages:\n";
$packages = App\Models\TokenPackage::where('is_active', true)->get();
foreach ($packages as $package) {
    echo "  - ID: {$package->id}\n";
    echo "    Name: {$package->name}\n";
    echo "    Amount: {$package->token_amount} tokens\n";
    echo "    Price: \${$package->price}\n";
    echo "    Stripe Price ID: {$package->stripe_price_id}\n\n";
}

// 2. Test User
echo "Test Users:\n";
$users = App\Models\User::whereNotNull('stripe_id')->limit(3)->get();
foreach ($users as $user) {
    echo "  - ID: {$user->id}\n";
    echo "    Email: {$user->email}\n";
    echo "    Stripe ID: {$user->stripe_id}\n";
    $balance = $user->tokenBalance;
    if ($balance) {
        echo "    Balance: {$balance->balance} (Free: {$balance->free_balance}, Paid: {$balance->paid_balance})\n";
    }
    echo "\n";
}

// 3. Webhook Endpoint
echo "Webhook Configuration:\n";
echo "  - Endpoint: " . config('app.url') . "/api/webhooks/stripe/token-purchase\n";
echo "  - Webhook Secret: " . (config('services.stripe.webhook.secret') ? 'Configured ✓' : 'Missing ✗') . "\n";
echo "  - Stripe Key: " . (config('services.stripe.key') ? 'Configured ✓' : 'Missing ✗') . "\n";
echo "  - Stripe Secret: " . (config('services.stripe.secret') ? 'Configured ✓' : 'Missing ✗') . "\n\n";

echo "=== Webhook Test Command ===\n";
echo "stripe trigger checkout.session.completed --add checkout_session:mode=payment --add checkout_session:payment_status=paid\n\n";

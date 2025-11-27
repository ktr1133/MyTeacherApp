<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MaintenanceModeControl extends Command
{
    protected $signature = 'maintenance:control {action : up or down} {--secret= : Secret bypass token}';
    protected $description = 'Control maintenance mode with optional secret token';

    public function handle()
    {
        $action = $this->argument('action');
        $secret = $this->option('secret') ?? 'family-test-2025';

        if ($action === 'down') {
            $this->call('down', [
                '--secret' => $secret,
                '--render' => 'errors::503',
            ]);
            
            $this->info('✅ メンテナンスモード有効化完了！');
            $this->info('');
            $this->info('家族に共有するURL:');
            $this->line('https://my-teacher-app.com/' . $secret);
        } elseif ($action === 'up') {
            $this->call('up');
            $this->info('✅ メンテナンスモード解除完了！');
        } else {
            $this->error('Invalid action. Use "up" or "down".');
            return 1;
        }

        return 0;
    }
}

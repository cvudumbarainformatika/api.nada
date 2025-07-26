<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class MergeSwooleToDeploy extends Command
{
    protected $signature = 'git:deploy';

    protected $description = 'Merge branch swoole ke deploy dan push ke remote';

    public function handle()
    {
        $this->info('Fetching latest changes...');
        if (!$this->executeCommand(['git', 'fetch', 'origin'])) return;

        $this->info('Checkout ke branch deploy...');
        if (!$this->executeCommand(['git', 'checkout', 'deploy'])) return;

        $this->info('Merge dari origin/swoole...');
        if (!$this->executeCommand(['git', 'merge', 'origin/swoole', '--no-edit'])) return;

        $this->info('Push ke remote deploy...');
        if (!$this->executeCommand(['git', 'push', 'origin', 'deploy'])) return;

        $this->info('Kembali ke branch swoole...');
        if (!$this->executeCommand(['git', 'checkout', 'swoole'])) return;

        $this->info('✅ Merge selesai.');
    }

    protected function executeCommand(array $command): bool
    {
        $process = new Process($command);
        $process->setTimeout(null); // Biar gak ke-timeout

        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (!$process->isSuccessful()) {
            $this->error('❌ Error: ' . $process->getErrorOutput());
            return false;
        }

        return true;
    }
}

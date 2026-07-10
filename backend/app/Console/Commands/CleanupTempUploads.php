<?php

namespace App\Console\Commands;

use App\Services\TempUploadService;
use Illuminate\Console\Command;

class CleanupTempUploads extends Command
{
    protected $signature = 'uploads:cleanup-temp';

    protected $description = 'Borra imágenes temporales de subidas diferidas más viejas que uploads.temp_ttl_hours.';

    public function handle(TempUploadService $tempUploadService): int
    {
        $deleted = $tempUploadService->cleanupExpired();

        $this->info("Temporales borrados: {$deleted}");

        return self::SUCCESS;
    }
}

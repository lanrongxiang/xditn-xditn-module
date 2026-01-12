<?php

declare(strict_types=1);

namespace Modules\Common\Console;

use Illuminate\Console\Command;
use Modules\Common\Support\Upload\Uses\ChunkUpload;

class CleanupChunks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:cleanup:chunks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理过期的分片上传临时文件';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $chunkUpload = new ChunkUpload();

        $chunkUpload->cleanupExpiredChunks();

        $this->info('清理完成!');
    }
}

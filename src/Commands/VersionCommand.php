<?php

namespace XditnModule\Commands;

use Illuminate\Console\Command;
use XditnModule\XditnModule;

class VersionCommand extends Command
{
    protected $signature = 'xditn:module:version';

    protected $description = '显示 XditnModule 版本';

    public function handle(): void
    {
        $this->info(XditnModule::VERSION);
    }
}

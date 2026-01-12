<?php

namespace Modules\Ai\Services;

use Modules\Ai\Services\ParseFile\Factory;

class ParseFileService
{
    public function __construct(
        protected string $file
    ) {
    }

    public function parse()
    {
        Factory::make($this->file)->parse();
    }
}

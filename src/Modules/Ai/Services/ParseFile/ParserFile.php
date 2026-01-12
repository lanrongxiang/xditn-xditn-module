<?php

namespace Modules\Ai\Services\ParseFile;

abstract class ParserFile implements ParseFileInterface
{
    public function __construct(
        protected string $file
    ) {

    }
}

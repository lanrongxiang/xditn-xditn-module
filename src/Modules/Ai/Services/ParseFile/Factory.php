<?php

namespace Modules\Ai\Services\ParseFile;

use Modules\Openapi\Exceptions\FailedException;

class Factory
{
    public static function make(string $file): ParseFileInterface
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        return match ($ext) {
            'csv' => new Csv($file),
            'xlsx' => new Xlsx($file),
            'md' => new Markdown($file),
            'txt' => new Text($file),
            default => throw new FailedException("{$ext}文件类型不支持解析"),
        };
    }
}

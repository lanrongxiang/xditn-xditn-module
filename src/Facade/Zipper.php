<?php

declare(strict_types=1);

namespace XditnModule\Facade;

use Illuminate\Support\Facades\Facade;
use XditnModule\Support\Zip\Zipper as Zip;

/**
 * @method static Zip make(string $pathToFile)
 * @method static Zip zip(string $pathToFile)
 * @method static Zip phar(string $pathToFile)
 *
 * @see Zipper
 * Class Module
 */
class Zipper extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return Zip::class;
    }
}

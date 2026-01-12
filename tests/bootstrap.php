<?php

declare(strict_types=1);

/**
 * Test bootstrap file.
 *
 * Bootstrap file for PHPUnit tests
 */
ini_set('error_reporting', (string) (E_ALL & ~E_DEPRECATED));
error_reporting(E_ALL & ~E_DEPRECATED);

require __DIR__.'/../vendor/autoload.php';

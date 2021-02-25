#!/usr/bin/env php
<?php declare(strict_types=1);

foreach (array(__DIR__ . '/../../autoload.php', __DIR__ . '/../vendor/autoload.php', __DIR__ . '/vendor/autoload.php') as $file) {
    if (file_exists($file)) {
        define('PHPUNIT_COMPOSER_INSTALL', $file);

        break;
    }
}

unset($file);

if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    fwrite(STDERR, 'You need to set up the project dependencies using Composer:' . PHP_EOL . PHP_EOL . '    composer install' . PHP_EOL . PHP_EOL . 'You can learn all about Composer on https://getcomposer.org/.' . PHP_EOL);

    die(1);
}

require PHPUNIT_COMPOSER_INSTALL;

use rikmeijer\Bootstrap\Bootstrap;

print 'Generating functions...';
if ($argc === 2) {
    Bootstrap::generate($argv[1]);
} else {
    Bootstrap::generate(getcwd());
}
exit('Done');
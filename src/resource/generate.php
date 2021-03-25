<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\resource;

use function Functional\partial_right;

function generate(callable $writer): callable
{
    $generator = partial_right(static function (string $resourcesPath, string $namespace, callable $writer) use (&$generator): void {
        foreach (glob($resourcesPath . DIRECTORY_SEPARATOR . '*') as $resourceFilePath) {
            if (is_dir($resourceFilePath)) {
                $generator($resourceFilePath, trim($namespace . '\\' . basename($resourceFilePath), '\\'));
            } elseif (str_ends_with($resourceFilePath, '.php')) {
                $writer($resourceFilePath, $namespace);
            }
        }
    }, $writer);
    return $generator;
}
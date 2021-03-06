<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

use function Functional\partial_left;

function validate(array $config, callable $validator, string $property): mixed
{
    return $validator($config[$property] ?? null, partial_left(fn(string $property, string $message): bool => trigger_error($property . ' ' . $message, E_USER_ERROR), $property));
}
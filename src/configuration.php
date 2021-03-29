<?php declare(strict_types=1);
namespace rikmeijer\Bootstrap\configuration{
function __open(string $resourcePath) { $resourcePath = str_replace("\\", chr(47), $resourcePath); return \rikmeijer\Bootstrap\resource\open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . substr($resourcePath, strrpos($resourcePath, chr(47))), false); }
}namespace rikmeijer\Bootstrap\configuration { 
    if (function_exists("rikmeijer\Bootstrap\configuration\generate") === false) {
    function generate(string $from, string $namespace, bool $cached): void
{
	\rikmeijer\Bootstrap\configuration\__open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/configuration\\generate.php')(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap\configuration { 
    if (function_exists("rikmeijer\Bootstrap\configuration\path") === false) {
    function path(): string
{
	return \rikmeijer\Bootstrap\configuration\__open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/configuration\\path.php')(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap\configuration { 
    if (function_exists("rikmeijer\Bootstrap\configuration\validate") === false) {
    function validate(array $config, callable $validator, string $property): mixed
{
	return \rikmeijer\Bootstrap\configuration\__open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/configuration\\validate.php')(...func_get_args());
}

    }
}
<?php declare(strict_types=1);


namespace rikmeijer\Bootstrap;


use Functional as F;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class PHP
{
    public static function function (string $fqfn, string $parameters, string $returnType, string $code): string
    {
        return PHP_EOL . '    if (function_exists(' . self::export($fqfn) . ') === false) {' . PHP_EOL . '        function ' . F\last(explode('\\', $fqfn)) . ' (' . $parameters . ') ' . ($returnType !== '' ? ': ' . $returnType : '') . '{' . PHP_EOL . '            ' . $code . PHP_EOL . '        }' . PHP_EOL . '    }' . PHP_EOL;
    }

    public static function export(mixed $variable): string
    {
        if ($variable instanceof ReflectionParameter) {
            $type = $variable->getType();
            if ($type === null) {
                return '$' . $variable->getName();
            }
            $typeHint = '';
            if ($type->allowsNull()) {
                $typeHint .= '?';
            }
            $typeHint .= self::export($type);

            if ($variable->isDefaultValueAvailable() === false) {
                $default = '';
            } elseif ($variable->isDefaultValueConstant()) {
                $default = ' = ' . $variable->getDefaultValueConstantName();
            } else {
                $default = ' = ' . self::export($variable->getDefaultValue());
            }

            return $typeHint . ' $' . $variable->getName() . $default;
        }

        if ($variable instanceof ReflectionUnionType) {
            return implode('|', array_map([self::class, 'export'], $variable->getTypes()));
        }

        if ($variable instanceof ReflectionNamedType) {
            return ($variable->isBuiltin() === false ? '\\' : '') . $variable->getName();
        }
        return var_export($variable, true);
    }

    public static function deductContextFromFile(string $resourcePath): array
    {
        $resourceFileContents = file_get_contents($resourcePath);

        $tokens = token_get_all($resourceFileContents, TOKEN_PARSE);

        $findNextToken = F\partial_left([__CLASS__, 'tokenFinder'], $tokens);
        $collectTokensUpTo = F\partial_left([__CLASS__, 'tokenCollector'], $tokens);

        $context = [];
        if ($findNextToken(T_NAMESPACE) !== null) {
            $context['namespace'] = $findNextToken(T_NAME_QUALIFIED)[1];
        }

        if ($findNextToken(T_RETURN) === null) {
            return $context;
        }

        $context['parameters'] = '';
        if ($findNextToken(T_FUNCTION, 3) !== null) {
            $findNextToken("(");
            foreach ($collectTokensUpTo(")") as $parameterToken) {
                if (is_string($parameterToken)) {
                    $context['parameters'] .= $parameterToken;
                } else {
                    $context['parameters'] .= $parameterToken[1];
                }
            }


            $functionSignatureTokens = $collectTokensUpTo('{');
            $functionSignatureTokenFinder = F\partial_left([__CLASS__, 'tokenFinder'], $functionSignatureTokens);
            if ($functionSignatureTokenFinder(":") !== null) {
                $context['returnType'] = '';
                while ($functionSignatureToken = array_shift($functionSignatureTokens)) {
                    $context['returnType'] .= $functionSignatureToken[1];
                }
                $context['returnType'] = trim($context['returnType']);
            }
        }
        return $context;
    }


    public static function tokenFinder(array &$tokens, mixed $id, ?int $maxTokenDistance = null): null|array|string
    {
        while ($token = array_shift($tokens)) {
            if ($maxTokenDistance > 0) {
                $maxTokenDistance--;
            } elseif ($maxTokenDistance === 0) {
                return null;
            }
            if (is_string($id)) {
                if ($token === $id) {
                    return $token;
                }
            } elseif (is_int($id)) {
                if ($token[0] === $id) {
                    return $token;
                }
            }
        }
        return null;
    }

    public static function tokenCollector(array &$tokens, mixed $id): null|array
    {
        $buffer = [];
        while ($token = array_shift($tokens)) {
            if (is_string($id)) {
                if ($token === $id) {
                    return $buffer;
                }
            } elseif (is_int($id)) {
                if ($token[0] === $id) {
                    return $buffer;
                }
            }
            $buffer[] = $token;
        }
        array_unshift($tokens, ...$buffer);
        return null;
    }
}
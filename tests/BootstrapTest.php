<?php /** @noinspection PhpIncludeInspection */
declare(strict_types=1);

namespace rikmeijer\Bootstrap\tests;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use rikmeijer\Bootstrap\PHP;
use function fread;
use function fwrite;
use function rikmeijer\Bootstrap\configuration\validate;
use function rikmeijer\Bootstrap\configure;
use function rikmeijer\Bootstrap\generate;

final class BootstrapTest extends TestCase
{
    private Functions $functions;

    /**
     * @dataProvider optionsProvider
     */
    public function test_WhenSimpleOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration(string $function, mixed $configValue): void
    {
        // Assert
        self::assertEquals($configValue, $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration($function, $configValue));
    }

    private function test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration(string $function, mixed $configValue): mixed
    {
        return validate([], $function($configValue), 'option');
    }

    private function getConfigurationRoot(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->getTestName();
    }

    private function getTestName(): string
    {
        return $this->getName(false);
    }

    private function activateBootstrap(): void
    {
        include $this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap.php';
    }

    /**
     * @dataProvider optionsProvider
     */
    public function testConfig_WhenSimpleOptionRequired_Expect_ErrorWhenNotSupplied(string $function): void
    {
        $this->test_When_OptionRequired_Expect_ErrorWhenNotSupplied($function);
    }

    private function test_When_OptionRequired_Expect_ErrorWhenNotSupplied(string $function): void
    {
        $this->expectError();
        $this->expectErrorMessage('option is not set and has no default value');
        validate([], $function(), 'option');
    }

    /**
     * @dataProvider optionsProvider
     */
    public function testConfig_WhenSimpleOptionRequired_Expect_NoErrorWhenSupplied(string $function, mixed $configValue): void
    {
        self::assertEquals($configValue, $this->testConfig_WhenOptionRequired_Expect_NoErrorWhenSupplied($function, $configValue));
    }


    /**
     * @dataProvider optionsProvider
     */
    public function testConfig_WhenSimpleOptionOptional_Expect_ConfiguredValueOverDefaultValue(string $function, mixed $configValue, mixed $defaultValue): void
    {
        self::assertEquals($configValue, $this->testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue($function, $configValue, $defaultValue));
    }

    private function testConfig_WhenOptionRequired_Expect_NoErrorWhenSupplied(string $function, mixed $configValue): mixed
    {
        return validate(['option' => $configValue], $function(), 'option');
    }


    private function testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue(string $function, mixed $configValue, mixed $defaultValue): mixed
    {
        self::assertNotEquals($configValue, $defaultValue);
        return validate(['option' => $configValue], $function($defaultValue), 'option');
    }

    const TYPES_NS = '\rikmeijer\Bootstrap\types';

    public function optionsProvider(): array
    {
        return [
            "boolean" => [
                self::TYPES_NS . '\boolean',
                true,
                false
            ],
            "integer" => [
                self::TYPES_NS . '\integer',
                1,
                2
            ],
            "float"   => [
                self::TYPES_NS . '\float',
                3.14,
                1.34
            ],
            "string"  => [
                self::TYPES_NS . '\string',
                "sometext",
                'anytext'
            ],
            "array"   => [
                self::TYPES_NS . '\arr',
                [
                    "some",
                    "value"
                ],
                [
                    "any",
                    "value"
                ]
            ]
        ];
    }

    public function test_When_PathOptionWithRelativeDefaultValue_Expect_AbsoluteDefaultValueToBeAvailableInConfiguration(): void
    {
        $path = $this->mkdir('somedir');
        $actual = $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration(self::TYPES_NS . '\path', 'somedir');
        self::assertEquals(fileinode($path), fileinode($actual));
    }

    public function test_When_PathOptionWithRelativeDefaultValueWithSubdirectories_Expect_JoinedAbsoluteDefaultValueToBeAvailableInConfiguration(): void
    {
        $path = $this->mkdir('somedir/somesubdir');
        $actual = $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration(self::TYPES_NS . '\path', 'somedir/somesubdir');
        self::assertEquals(fileinode($path), fileinode($actual));
    }

    public function test_When_PathOptionConfigurationContainsRelativePath_Expect_AbsolutePathOfConfiguration(): void
    {
        $path = $this->mkdir('somefolder');
        $actual = $this->testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue(self::TYPES_NS . '\path', 'somefolder', 'somedir');
        self::assertEquals(fileinode($path), fileinode($actual));
    }

    public function testWhenConfigurationRequiresPath_Expect_ErrorWhenNonSupplied(): void
    {
        $this->test_When_OptionRequired_Expect_ErrorWhenNotSupplied(self::TYPES_NS . '\path');
    }

    public function test_WhenFileOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration(): void
    {
        file_put_contents(implode(DIRECTORY_SEPARATOR, [$this->getConfigurationRoot(), 'somefile.txt']), 'Hello World');
        $actual = $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration(self::TYPES_NS . '\file', 'somefile.txt');
        self::assertEquals('Hello World', fread($actual("rb"), 11));
    }

    public function test_WhenFileOptionRequired_Expect_ErrorWhenNotSupplied(): void
    {
        $this->test_When_OptionRequired_Expect_ErrorWhenNotSupplied(self::TYPES_NS . '\file');
    }

    public function test_WhenFileOptionRequired_Expect_NoErrorWhenSupplied(): void
    {
        file_put_contents(implode(DIRECTORY_SEPARATOR, [$this->getConfigurationRoot(), 'somefile.txt']), 'Hello World');
        $actual = $this->testConfig_WhenOptionRequired_Expect_NoErrorWhenSupplied(self::TYPES_NS . '\file', 'somefile.txt');
        self::assertIsCallable($actual);
        self::assertEquals('Hello World', fread($actual("rb"), 11));
    }

    public function test_WhenFileOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue(): void
    {
        file_put_contents(implode(DIRECTORY_SEPARATOR, [$this->getConfigurationRoot(), 'somefile.txt']), 'Hello World');
        $actual = $this->testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue(self::TYPES_NS . '\file', 'somefile.txt', 'anyfile.txt');
        self::assertIsCallable($actual);
        self::assertEquals('Hello World', fread($actual("rb"), 11));
    }

    public function test_When_FileOptionWithPHPoutput_Expect_FunctionToOpenWritableFilestreamAndOutputPrinted(): void
    {
        $actual = $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration(self::TYPES_NS . '\file', "php://output");
        self::assertIsCallable($actual);
        $this->expectOutputString('Hello World');
        self::assertEquals(11, fwrite($actual("wb"), "Hello World"));
    }

    public function testWhen_ConfigurationOptionIsBinary_Expect_FunctionToExecuteBinaryAndReturnExitCode(): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Windows' => [
                'c:\\windows\\system32\\cmd.exe',
                '/C',
                "echo test"
            ],
            default => [
                '/usr/bin/bash',
                '-c',
                "echo test"
            ],
        };
        $actual = $this->testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue(self::TYPES_NS . '\binary', $command, [
            "/usr/bin/bash",
            "-c",
            "echo test2"
        ]);

        $this->expectOutputString("Testing test test..." . PHP_EOL . 'test' . PHP_EOL);
        self::assertEquals(0, $actual("Testing test test..."));
    }

    public function testWhen_ConfigurationOptionIsBinaryAndNamedArgumentsAreConfigured_Expect_OnlyThoseToBeReplaced(): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Windows' => [
                'c:\\windows\\system32\\cmd.exe',
                '/C',
                'cmd' => "echo test"
            ],
            default => [
                '/usr/bin/bash',
                '-c',
                'cmd' => "echo test"
            ],
        };

        $actual = $this->testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue(self::TYPES_NS . '\binary', $command, [
            "/usr/bin/bash",
            "-c",
            'cmd' => "echo test2"
        ]);

        $this->expectOutputString("Testing test test..." . PHP_EOL . 'test4' . PHP_EOL);
        self::assertEquals(0, $actual("Testing test test...", cmd: "echo test4"));
    }

    public function testWhen_BinarySimulation_Expect_BinaryNotReallyBeExecuted(): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Windows' => [
                'c:\\windows\\system32\\cmd.exe',
                '/C',
                "echo test"
            ],
            default => [
                '/usr/bin/bash',
                '-c',
                "echo test"
            ],
        };

        $this->functions->createConfig('config', [
            'types/binary' => ['simulation' => true]
        ]);
        $actual = $this->testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue(self::TYPES_NS . '\binary', $command, [
            "/usr/bin/bash",
            "-c",
            "echo test2"
        ]);

        $this->expectOutputString("What is this?..." . PHP_EOL . '(s) ' . escapeshellcmd($command[0]) . ' ' . $command[1] . ' ' . escapeshellarg($command[2]));
        self::assertEquals(0, $actual("What is this?..."));
    }

    public function testWhen_ConfigurationOptionIsRequiredAndBinary_Expect_ErrorNoneConfigured(): void
    {
        $this->test_When_OptionRequired_Expect_ErrorWhenNotSupplied(self::TYPES_NS . '\binary');
    }

    private function getBootstrapFQFN(string $function): string
    {
        return 'rikmeijer\\Bootstrap\\' . $function;
    }

    private function createFunction(string $resourceName, string $content, string $configNS = null): string
    {
        $directory = dirname($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName);
        if (str_contains($resourceName, '/')) {
            is_dir($directory) || mkdir($directory, 0777, true);
        }
        $file = $this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName . '.php';

        $context = PHP::deductContextFromString(substr($content, 0, strpos($content, 'return')));
        $fqfn = '\\';
        $functionIdentifier = str_replace('/', '\\', $resourceName);
        if ($configNS !== null) {
            $fqfn .= $configNS . '\\' . $functionIdentifier;
        } elseif (array_key_exists('namespace', $context) === false) {
            $fqfn .= $this->getBootstrapFQFN($this->getTestName()) . '\\' . $functionIdentifier;
        } elseif (($lastSlashPosition = strrpos($functionIdentifier, '\\')) !== false) {
            $fqfn .= $context['namespace'] . '\\' . substr($functionIdentifier, $lastSlashPosition + 1);
        } else {
            $fqfn .= $context['namespace'] . '\\' . $functionIdentifier;
        }

        $code = str_replace(['${FQFN}', '${FN}'], [$fqfn, substr($fqfn, 1)], $content);
        file_put_contents($file, $code);
        return $fqfn;
    }

    public function test_When_CustomOptionsAreConfigured_Expect_IgnoredIfNotInSchema(): void
    {
        $this->functions->createConfig('config', ['resource' => ['option2' => "custom"]]);
        generate();
        $this->activateBootstrap();

        $function = configure(static function (array $configuration): array {
            return $configuration;
        }, ["option" => (self::TYPES_NS . '\string')("default")], 'resource');

        $configuration = $function();

        self::assertEquals("default", $configuration['option']);
        self::assertArrayNotHasKey('option2', $configuration);
    }

    public function testResource(): void
    {
        $f = $this->createFunction('resource', '<?php ' . PHP_EOL . 'return static function() {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        generate();
        $this->activateBootstrap();

        self::assertEquals('Yes!', $f()->status);
    }

    public function test_When_FunctionsNotGenerated_Expect_FunctionsNotExisting(): void
    {
        $f = $this->createFunction('resourceFunc', '<?php ' . PHP_EOL . 'return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        $this->expectError();
        $this->activateBootstrap();
        self::assertFalse(function_exists($f));
    }

    public function testWhen_VoidCalled_Expect_FunctionNotReturning(): void
    {
        $f = $this->createFunction('resourceFuncVoid', '<?php namespace rikmeijer\\Bootstrap\\fvoid; return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) : void {' . PHP_EOL . ' ' . PHP_EOL . '};');

        generate();
        $this->activateBootstrap();

        $args = [
            'foo',
            null,
            $this->createMock(ReflectionFunction::class),
            3.14
        ];

        self::assertNull($f(...$args));
    }

    public function testWhen_Called_Expect_FunctionAvailableAsFunctionUnderNS(): void
    {
        $this->functions->createConfig('config', ['BOOTSTRAP' => ['namespace' => 'rikmeijer\\Bootstrap\\f']]);
        $f = $this->createFunction('test/resourceFunc', '<?php return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4 = 0) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};', 'rikmeijer\\Bootstrap\\f');

        generate();
        $this->activateBootstrap();
        $args = [
            'foo',
            null,
            $this->createMock(ReflectionFunction::class)
        ];
        self::assertEquals('Yes!', $f(...$args)->status);
    }

    public function testWhen_CalledDeeper_Expect_FunctionAvailableAsFunctionUnderNS(): void
    {
        $f = $this->createFunction('test/test/resourceFunc', '<?php return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, string $arg4 = "") {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        generate();
        $this->activateBootstrap();
        $args = [
            'foo',
            null,
            $this->createMock(ReflectionFunction::class)
        ];
        self::assertEquals('Yes!', $f(...$args)->status);
    }

    public function testWhen_ResourcesAreGenerated_Expect_ResourcesAvailableAsFunctions(): void
    {
        $this->functions->createConfig('config', [
            'BOOTSTRAP'              => ['namespace' => 'rikmeijer\\Bootstrap\\f3'],
            'test/test/resourceFunc' => ['status' => 'Yesss!']
        ]);
        $f0 = $this->createFunction('resourceFunc', '<?php return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};', 'rikmeijer\\Bootstrap\\f3');
        $f1 = $this->createFunction('test/resourceFunc', '<?php return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};', 'rikmeijer\\Bootstrap\\f3');
        $f2 = $this->createFunction('test/test/resourceFunc', '<?php return \\' . $this->getBootstrapFQFN('configure') . '(static function(array $configuration, $arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => $configuration["status"]];' . PHP_EOL . '}, ["status" => ' . self::TYPES_NS . '\\string("No!")]);', 'rikmeijer\\Bootstrap\\f3');

        generate();

        self::assertFileExists($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap.php');

        $this->activateBootstrap();

        $args = [
            'foo',
            null,
            $this->createMock(ReflectionFunction::class),
            3.14
        ];

        self::assertEquals('Yes!', $f0(...$args)->status);
        self::assertEquals('Yes!', $f1(...$args)->status);
        self::assertEquals('Yesss!', $f2(...$args)->status);
    }

    public function testWhen_ResourcesAreGeneratedWithinNS_Expect_ResourceConfigsAvailableAsFunctions(): void
    {
        $this->functions->createConfig('config', ['test/test/resourceFunc' => ['status' => 'Yesss!']]);
        $f0 = $this->createFunction('resourceFunc', '<?php namespace rikmeijer\\Bootstrap\\f2; return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');
        $f1 = $this->createFunction('test/resourceFunc', '<?php namespace rikmeijer\\Bootstrap\\f2\\test; return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');
        $f2 = $this->createFunction('test/test/resourceFunc', '<?php namespace rikmeijer\\Bootstrap\\f2\\test\\test; return \\' . $this->getBootstrapFQFN('configure') . '(static function(array $configuration, $arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => $configuration["status"]];' . PHP_EOL . '}, ["status" => ' . self::TYPES_NS . '\\string("No!")]);');

        generate();

        self::assertFileExists($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap.php');

        $this->activateBootstrap();
        $args = [
            'foo',
            null,
            $this->createMock(ReflectionFunction::class),
            3.14
        ];

        self::assertEquals('Yes!', $f0(...$args)->status);
        self::assertEquals('Yes!', $f1(...$args)->status);
        self::assertEquals('Yesss!', $f2(...$args)->status);
    }

    public function testResourceWhenExtraArgumentsArePassed_Expect_ParametersAvailable(): void
    {
        $f = $this->createFunction('resource', '<?php return static function(string $extratext) { ' . PHP_EOL . '   return (object)["status" => "Yes!" . $extratext]; };');
        generate();
        $this->activateBootstrap();

        self::assertEquals('Yes!Hello World', $f('Hello World')->status);
    }

    public function testWhenConfigurationSectionMatchesResourcesName_ExpectConfigurationToBePassedToBootstrapper(): void
    {
        $value = uniqid('', true);
        $f = $this->createFunction('resource', '<?php namespace ' . $this->getBootstrapFQFN($this->getTestName()) . '; return \\' . $this->getBootstrapFQFN('configure') . '(static function(array $configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '}, ["status" => ' . self::TYPES_NS . '\\string("' . $value . '")]);');

        generate();
        $this->activateBootstrap();

        self::assertEquals($value, $f()->status);
    }

    private function mkdir(string $path): string
    {
        $path = $this->getConfigurationRoot() . DIRECTORY_SEPARATOR . $path;
        if (!is_dir($path) && !mkdir($path, recursive: true)) {
            trigger_error("Unable to create " . $path, E_USER_ERROR);
        }
        return $path;
    }

    public function testWhenResourceDependentOfOtherResource_Expect_ResourcesVariableCallableAndReturningDependency(): void
    {
        $value = uniqid('', true);

        $dependency = $this->createFunction('dependency', '<?php namespace ' . $this->getBootstrapFQFN($this->getTestName()) . ';  return \\' . $this->getBootstrapFQFN('configure') . '(static function(array $configuration) : object {' . PHP_EOL . '   return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '}, ["status" => ' . self::TYPES_NS . '\\string("' . $value . '")]);');

        $f = $this->createFunction('resourceDependent', '<?php ' . PHP_EOL . 'return static function() { ' . PHP_EOL . '   return (object)["status" => ' . $dependency . '()->status]; ' . PHP_EOL . '};');

        generate();
        $this->activateBootstrap();

        self::assertEquals($value, $f()->status);
    }

    public function testWhenResourceDependentOfOtherResourceWithExtraArguments_Expect_ExtraParametersAvailableInDependency(): void
    {
        $value = uniqid('', true);

        $dependency = $this->createFunction('dependency', '<?php namespace ' . $this->getBootstrapFQFN($this->getTestName()) . '; return \\' . $this->getBootstrapFQFN('configure') . '(function(array $configuration, string $extratext) : object { ' . PHP_EOL . '   return (object)["status" => $configuration["status"] . $extratext]; ' . PHP_EOL . '}, ["status" => ' . self::TYPES_NS . '\\string("' . $value . '")]);');
        $f = $this->createFunction('resourceDependent', '<?php' . PHP_EOL . 'return static function() {' . PHP_EOL . '   return (object)["status" => ' . $dependency . '("Hello World!")->status]; ' . PHP_EOL . '};');

        generate();
        $this->activateBootstrap();

        self::assertEquals($value . 'Hello World!', $f()->status);
    }

    public function testWhenNoConfigurationIsRequired_ExpectOnlyDependenciesInjectedByBootstrap(): void
    {
        $value = uniqid('', true);

        $dependency2 = $this->createFunction('dependency2', '<?php namespace ' . $this->getBootstrapFQFN($this->getTestName()) . '; return \\' . $this->getBootstrapFQFN('configure') . '(function(array $configuration) : object { ' . PHP_EOL . '   return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '}, ["status" => ' . self::TYPES_NS . '\\string("' . $value . '")]);');

        $f = $this->createFunction('resourceDependent2', '<?php ' . PHP_EOL . 'return static function() { ' . PHP_EOL . '   return (object)["status" => ' . $dependency2 . '()->status]; ' . PHP_EOL . '};');

        generate();
        $this->activateBootstrap();

        self::assertEquals($value, $f()->status);
    }

    public function testResourceCache(): void
    {
        $f = $this->createFunction('resourceCache', '<?php ' . PHP_EOL . 'return static function() { ' . PHP_EOL . '   return (object)["status" => "Yes!"]; ' . PHP_EOL . '};');

        generate();
        $this->activateBootstrap();

        self::assertEquals('Yes!', $f()->status);

        $this->createFunction('resourceCache', '<?php ' . PHP_EOL . 'return static function() { ' . PHP_EOL . '   return (object)["status" => "No!"];' . PHP_EOL . '};');
        self::assertNotInstanceOf(Closure::class, $f());
        self::assertEquals('Yes!', $f()->status);

    }

    protected function setUp(): void
    {
        $this->mkdir('bootstrap');
        $this->functions = new Functions($this->getConfigurationRoot());
    }

    protected function tearDown(): void
    {
        unset($this->functions);
        $this->deleteDirRecursively($this->getConfigurationRoot());
    }

    private function deleteDirRecursively(string $dir): void
    {
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $tmpFile) {
            if (is_file($tmpFile)) {
                @unlink($tmpFile);
            } elseif (is_dir($tmpFile)) {
                $this->deleteDirRecursively($tmpFile);
            }
        }
        @rmdir($dir);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function exposeGeneratedFunctions(): void
    {
        print file_get_contents($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap.php');
    }
}

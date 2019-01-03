<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests;

use Generator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SignpostMarv\DaftFramework\Framework as BaseFramework;
use SignpostMarv\DaftFramework\HttpHandler as BaseHttpHandler;
use SignpostMarv\DaftFramework\Logging\CatchingHttpHandler;
use SignpostMarv\DaftFramework\Logging\Framework;
use SignpostMarv\DaftFramework\Logging\HttpHandler;
use SignpostMarv\DaftFramework\Tests\ImplementationTest as Base;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\PlainTextHandler;

class ImplementationTest extends Base
{
    const RemapFrameworks = [
        BaseFramework::class => Framework::class,
        BaseHttpHandler::class => HttpHandler::class,
    ];

    /**
    * @psalm-suppress InterfaceInstantiation
    */
    public function DataProviderGoodSources() : Generator
    {
        /**
        * @var array $args
        */
        foreach (parent::DataProviderGoodSources() as $args) {
            /**
            * @var array $loggerArgs
            * @var string $loggerArgs[0]
            */
            foreach ($this->DataProviderLoggerArguments() as $loggerArgs) {
                $loggerImplementation = $loggerArgs[0];

                if (
                    ! class_exists($loggerImplementation) ||
                    ! is_a($loggerImplementation, LoggerInterface::class, true)
                ) {
                    static::assertTrue(class_exists($loggerImplementation));
                    static::assertTrue(is_a(
                        $loggerImplementation,
                        LoggerInterface::class,
                        true
                    ));

                    return;
                }

                /**
                * @var LoggerInterface
                */
                $logger = new $loggerImplementation(...array_slice($loggerArgs, 1));

                $implementation = (string) array_shift($args);
                $implementation = self::RemapFrameworks[$implementation] ?? $implementation;

                /**
                * @var array<string, mixed[]> $postConstructionCalls
                */
                $postConstructionCalls = array_shift($args);

                array_unshift($args, $implementation, $postConstructionCalls, $logger);

                yield $args;

                if (HttpHandler::class === $args[0]) {
                    $args[0] = CatchingHttpHandler::class;

                    /**
                    * @var array $whoopsArguments
                    */
                    foreach ($this->DataProviderWhoopsHandlerArguments() as $whoopsArguments) {
                        $args5 = (array) $args[5];

                        $args5[HandlerInterface::class] = (array) $whoopsArguments[0];

                        $args[5] = $args5;

                        yield $args;
                    }
                }
            }
        }
    }

    public function DataProviderLoggerArguments() : Generator
    {
        yield from [
            [
                NullLogger::class,
            ],
        ];
    }

    public function DataProviderWhoopsHandlerArguments() : Generator
    {
        yield from [
            [
                [
                    PlainTextHandler::class => [],
                ],
            ],
        ];
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    * @param mixed ...$implementationArgs
    *
    * @dataProvider DataProviderGoodSources
    */
    public function testEverythingInitialisesFine(
        string $implementation,
        array $postConstructionCalls,
        ...$implementationArgs
    ) : BaseFramework {
        /**
        * @var Framework|object|null $instance
        */
        $instance = parent::testEverythingInitialisesFine(
            $implementation,
            $postConstructionCalls,
            ...$implementationArgs
        );

        list($logger) = $implementationArgs;

        static::assertInstanceOf(LoggerInterface::class, $logger);

        static::assertTrue(
            is_object($instance) &&
            (
                ($instance instanceof Framework) ||
                ($instance instanceof HttpHandler)
            )
        );

        /**
        * @var Framework $instance
        */
        $instance = $instance;

        static::assertSame($logger, $instance->ObtainLogger());

        return $instance;
    }

    protected function extractDefaultFrameworkArgs(array $implementationArgs) : array
    {
        list(, $baseUrl, $basePath, $config) = $implementationArgs;

        return [$baseUrl, $basePath, $config];
    }
}

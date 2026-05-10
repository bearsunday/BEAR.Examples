<?php

declare(strict_types=1);

namespace MyVendor\Cms;

use BEAR\AppMeta\Meta;
use BEAR\Package\Injector as PackageInjectorFacade;
use BEAR\Package\Injector\PackageInjector;
use MyVendor\Cms\Module\AsyncModule;
use Ray\Di\AbstractModule;
use Ray\Di\InjectorInterface;

use function dirname;
use function str_starts_with;
use function substr;

/** @SuppressWarnings("PHPMD.StaticAccess") */
final class Injector
{
    /** @codeCoverageIgnore */
    private function __construct()
    {
    }

    /** @param non-empty-string $context */
    public static function getInstance(string $context): InjectorInterface
    {
        if (str_starts_with($context, 'async-')) {
            return self::getAsyncInstance($context);
        }

        return PackageInjectorFacade::getInstance(__NAMESPACE__, $context, dirname(__DIR__));
    }

    /** @param non-empty-string $context */
    public static function getOverrideInstance(string $context, AbstractModule $overrideModule): InjectorInterface
    {
        if (str_starts_with($context, 'async-')) {
            $asyncModule = self::asyncModule($context);
            $asyncModule->override($overrideModule);

            return PackageInjector::factory(
                new Meta(__NAMESPACE__, $context, dirname(__DIR__)),
                self::workerContextFromAsyncContext($context),
                $asyncModule,
            );
        }

        return PackageInjectorFacade::getOverrideInstance(__NAMESPACE__, $context, dirname(__DIR__), $overrideModule);
    }

    /** @param non-empty-string $context */
    public static function workerContextFromAsyncContext(string $context): string
    {
        return str_starts_with($context, 'async-') ? substr($context, 6) : $context;
    }

    /** @param non-empty-string $context */
    private static function getAsyncInstance(string $context): InjectorInterface
    {
        return PackageInjector::factory(
            new Meta(__NAMESPACE__, $context, dirname(__DIR__)),
            self::workerContextFromAsyncContext($context),
            self::asyncModule($context),
        );
    }

    /** @param non-empty-string $context */
    private static function asyncModule(string $context): AsyncModule
    {
        $module = new AsyncModule(new Meta(__NAMESPACE__, $context, dirname(__DIR__)));
        $module->setWorkerContext(self::workerContextFromAsyncContext($context));

        return $module;
    }
}

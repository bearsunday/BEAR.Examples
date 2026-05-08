<?php

declare(strict_types=1);

namespace MyVendor\Cms\Provider;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Util\HtmlFilter;
use Ray\Di\ProviderInterface;

/**
 * CommonMarkConverter cannot be bound via toInstance(): the converter wires
 * Closures into its event dispatcher, and Ray.Compiler serialises bound
 * instances when compiling the DI script for prod contexts. A Provider keeps
 * construction lazy so the converter is built fresh after compilation.
 *
 * @implements ProviderInterface<CommonMarkConverter>
 */
final class CommonMarkConverterProvider implements ProviderInterface
{
    public function get(): CommonMarkConverter
    {
        return new CommonMarkConverter([
            'html_input' => HtmlFilter::ESCAPE,
            'allow_unsafe_links' => false,
        ]);
    }
}

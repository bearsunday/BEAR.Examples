<?php

declare(strict_types=1);

namespace MyVendor\Cms\Example\ImportedCatalog\Resource\App;

use BEAR\Resource\ResourceObject;

/**
 * Tiny imported app resource used by the Application import companion example.
 */
class Status extends ResourceObject
{
    public function onGet(): static
    {
        $this->body = [
            'app' => 'imported-catalog',
            'purpose' => 'BEAR.Sunday ImportAppModule companion example',
        ];

        return $this;
    }
}

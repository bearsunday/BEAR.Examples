<?php

declare(strict_types=1);

namespace MyVendor\Cms\Fake;

use Override;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\DefaultView;
use Ray\AuraSqlModule\Pagerfanta\DefaultRouteGenerator;
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\MediaQuery\Exception\LogicException;
use Ray\MediaQuery\PagesInterface;

use function count;
use function max;

/**
 * In-memory PagesInterface backed by Pagerfanta's ArrayAdapter.
 *
 * The production Ray.MediaQuery path returns Ray\MediaQuery\Pages, which is
 * final and PDO-backed. This fake keeps the same Countable + array-access
 * contract and returns the same Ray\AuraSqlModule Page object shape.
 */
final class FakePages implements PagesInterface
{
    /** @param list<mixed> $items */
    public function __construct(
        private readonly array $items,
        private readonly int $perPage,
        private readonly string $queryTemplate = '/{?page}',
    ) {
    }

    #[Override]
    public function offsetExists($offset): bool
    {
        return (bool) $this->offsetGet($offset);
    }

    #[Override]
    public function offsetGet($offset): Page
    {
        $pagerfanta = new Pagerfanta(new ArrayAdapter($this->items));
        $pagerfanta->setMaxPerPage(max(1, $this->perPage));
        $pagerfanta->setCurrentPage(max(1, (int) $offset));

        $page = new Page($pagerfanta, new DefaultRouteGenerator($this->queryTemplate), new DefaultView(), []);
        $page->maxPerPage = $pagerfanta->getMaxPerPage();
        $page->current = $pagerfanta->getCurrentPage();
        $page->hasNext = (bool) $pagerfanta->hasNextPage();
        $page->hasPrevious = $pagerfanta->hasPreviousPage();
        $page->data = $pagerfanta->getCurrentPageResults();
        $page->total = $pagerfanta->getNbResults();

        return $page;
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function offsetSet(mixed $offset, mixed $value): never
    {
        unset($offset, $value);

        throw new LogicException('Read only');
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function offsetUnset(mixed $offset): never
    {
        unset($offset);

        throw new LogicException('Read only');
    }

    #[Override]
    public function count(): int
    {
        return count($this->items);
    }
}

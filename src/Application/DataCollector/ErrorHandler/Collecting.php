<?php declare(strict_types=1);

/**
 * Error handler that collects all events/errors into an array.
 */

namespace Bartlett\CompatInfo\Application\DataCollector\ErrorHandler;

use Bartlett\CompatInfo\Application\DataCollector\ErrorHandler;

use Doctrine\Common\Collections\ArrayCollection;

use PhpParser\Error;

/**
 * @phpstan-extends ArrayCollection<int, Error>
 * @since 5.4.0, 6.0.0
 */
final class Collecting extends ArrayCollection implements ErrorHandler
{
    /**
     * {@inheritDoc}
     * @return void
     */
    public function handleError(Error $error)
    {
        $this->add($error);
    }

    /**
     * {@inheritDoc}
     */
    public function getErrors(): array
    {
        return $this->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function hasErrors(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * {@inheritDoc}
     */
    public function clearErrors(): void
    {
        $this->clear();
    }
}

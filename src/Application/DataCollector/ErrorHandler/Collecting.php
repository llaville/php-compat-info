<?php declare(strict_types=1);

/**
 * Error handler that collects all events/errors into an array.
 */

namespace Bartlett\CompatInfo\Application\DataCollector\ErrorHandler;

use Bartlett\CompatInfo\Application\DataCollector\ErrorHandler;

use Doctrine\Common\Collections\ArrayCollection;

use PhpParser\Error;

/**
 * @since 5.4.0, 6.0.0
 */
final class Collecting extends ArrayCollection implements ErrorHandler
{
    /**
     * {@inheritDoc}
     */
    public function handleError(Error $error)
    {
        $this->add($error);
    }

    /**
     * Get collected errors.
     *
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->toArray();
    }

    /**
     * Check whether there are any errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Reset/clear collected errors.
     *
     * @return void
     */
    public function clearErrors(): void
    {
        $this->clear();
    }
}

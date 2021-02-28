<?php declare(strict_types=1);

/**
 * Easy for debugging to know where event/error occurs.
 */

namespace Bartlett\CompatInfo\Application\DataCollector\ErrorHandler;

use Bartlett\CompatInfo\Application\DataCollector\ErrorHandler;

use PhpParser\Error;

/**
 * @since 5.4.0, 6.0.0
 */
final class Throwing implements ErrorHandler
{
    /**
     * {@inheritDoc}
     * @return void
     */
    public function handleError(Error $error)
    {
        throw $error;
    }

    /**
     * {@inheritDoc}
     */
    public function hasErrors(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getErrors(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function clearErrors(): void
    {
    }
}

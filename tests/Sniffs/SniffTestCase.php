<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Tests\Sniffs;

use Bartlett\CompatInfo\Tests\TestCase;

/**
 * Base class for all sniffs test case
 *
 * @since Class available since Release 5.4.0
 */
abstract class SniffTestCase extends TestCase
{
    /**
     * {@inheritDoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$fixtures .= 'sniffs' . DIRECTORY_SEPARATOR;
    }
}

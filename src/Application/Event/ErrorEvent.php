<?php declare(strict_types=1);

/**
 * The ERROR event allows you to learn more about PHP-Parser error raised.
 *
 * The event listener method receives a Symfony\Component\EventDispatcher\GenericEvent
 * instance with following arguments :
 * - `source` data source identifier
 * - `file`   current file parsed in the data source
 * - `error`  PHP Parser error message
 */

namespace Bartlett\CompatInfo\Application\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @since Release 6.0.0
 */
final class ErrorEvent extends GenericEvent
{
}

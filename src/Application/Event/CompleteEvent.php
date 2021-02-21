<?php declare(strict_types=1);

/**
 * The COMPLETE event allows you to be notified when a data source parsing
 * is over.
 *
 * The event listener method receives a Symfony\Component\EventDispatcher\GenericEvent
 * instance with following arguments :
 * - `source` data source identifier
 */

namespace Bartlett\CompatInfo\Application\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @since Release 6.0.0
 */
final class CompleteEvent extends GenericEvent
{
}

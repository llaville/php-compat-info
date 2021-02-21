<?php declare(strict_types=1);

/**
 * The SUCCESS event allows you to get the AST (Abstract Syntax Tree)
 * from a live request. A cached request will not trigger this event.
 *
 * The event listener method receives a Symfony\Component\EventDispatcher\GenericEvent
 * instance with following arguments :
 * - `source` data source identifier
 * - `file`   current file parsed in the data source
 * - `ast`    the Abstract Syntax Tree result (serialized)
 */

namespace Bartlett\CompatInfo\Application\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @since Release 6.0.0
 */
final class SuccessEvent extends GenericEvent
{
}

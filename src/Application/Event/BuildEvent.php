<?php declare(strict_types=1);

/**
 * The BUILD event allows you to learn what are processes applied during AST building.
 *
 * The event listener method receives a Symfony\Component\EventDispatcher\GenericEvent
 * instance with following arguments :
 * - `method` current process
 * - `node`   current node visited
 */

namespace Bartlett\CompatInfo\Application\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @since Release 6.0.0
 */
final class BuildEvent extends GenericEvent
{
}

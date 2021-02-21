<?php declare(strict_types=1);

/**
 * The SNIFF event allows you to learn what are sniff processes during AST traverse.
 */

namespace Bartlett\CompatInfo\Application\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @since Release 6.0.0
 */
final class SniffEvent extends GenericEvent
{
}

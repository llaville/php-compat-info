<?php declare(strict_types=1);

/**
 * The PROGRESS event allows you to know what file of the data source
 * is ready to be parsed.
 *
 * The event listener method receives a Symfony\Component\EventDispatcher\GenericEvent
 * instance with following arguments :
 * - `source`  data source identifier
 * - `queue`   files list in the data source to parse (Symfony Finder instance)
 * - `closure` a closure to process on each file of `queue`
 */

namespace Bartlett\CompatInfo\Application\Event;

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @since Release 6.0.0
 */
final class ProgressEvent extends GenericEvent
{
    /**
     * @return SplFileInfo[]
     */
    public function getQueue(): iterable
    {
        return $this->hasArgument('queue') ? $this->getArgument('queue') : [];
    }

    public function getClosure(): callable
    {
        return $this->hasArgument('closure') ? $this->getArgument('closure') : function () { };
    }
}

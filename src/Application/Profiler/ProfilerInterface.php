<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\Profiler;

use Exception;

/**
 * @since Release 6.0.0
 */
interface ProfilerInterface extends CollectorInterface
{
    /**
     * Resets all Collectors to theirs initial state.
     */
    public function reset(): void;

    /**
     * Collects all data from each Collector attached to this Profiler.
     *
     * @return Profile
     * @throws Exception
     */
    public function collect(): Profile;
}

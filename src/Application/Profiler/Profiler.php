<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\Profiler;

use Bartlett\CompatInfo\Application\DataCollector\DataCollectorInterface;

use Ramsey\Uuid\Uuid;

use Exception;
use function array_merge;

/**
 * @since Release 5.4.0
 */
final class Profiler implements CollectorInterface
{
    use CollectorTrait;

    /** @var string */
    private $token;

    public function __construct(?string $token)
    {
        if (empty($token)) {
            $this->token = Uuid::uuid4()->toString();
        } else {
            $this->token = $token;
        }
    }

    /**
     * Resets all Collectors to theirs initial state.
     */
    public function reset(): void
    {
        foreach ($this->collectors as $collector) {
            $collector->reset();
        }
    }

    /**
     * Collects all data from each Collector attached to this Profiler.
     *
     * @return Profile
     * @throws Exception
     */
    public function collect(): Profile
    {
        $data = ['files' => [], 'errors' => []];

        foreach ($this->getCollectors() as $collector) {
            /** @var DataCollectorInterface $collector */
            $data[$collector->getName()] = $collector->getData();
            $data['files'] = array_merge($data['files'], $collector->getFiles());
            $data['errors'] = array_merge($data['errors'], $collector->getErrors());
        }

        return new Profile($this->token, $data);
    }
}
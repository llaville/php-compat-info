<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\Profiler;

/**
 * @since Release 5.4.0
 */
final class Profile implements CollectorInterface
{
    /** @var string */
    private $token;

    /** @var array[] */
    private $data;

    use CollectorTrait;

    /**
     * Profile constructor.
     *
     * @param string $token
     * @param array[] $data
     */
    public function __construct(string $token, iterable $data)
    {
        $this->token = $token;
        $this->data = $data;
    }

    /**
     * @return array[]
     */
    public function getData(): array
    {
        return [$this->token => $this->data];
    }
}

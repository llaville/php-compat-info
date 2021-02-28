<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\Query\Analyser\Compatibility;

use Bartlett\CompatInfo\Application\Query\QueryInterface;

/**
 * @since Release 6.0.0
 */
final class GetCompatibilityQuery implements QueryInterface
{
    /** @var string  */
    private $source;
    /** @var bool  */
    private $stopOnFailure;

    public function __construct(string $source, bool $stopOnFailure)
    {
        $this->source = $source;
        $this->stopOnFailure = $stopOnFailure;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return bool
     */
    public function isStopOnFailure(): bool
    {
        return $this->stopOnFailure;
    }
}

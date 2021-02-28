<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\Sniffs;

use ArrayIterator;
use Countable;
use IteratorAggregate;

use function array_key_exists;
use function array_merge;
use function substr_count;

/**
 * @phpstan-implements IteratorAggregate<string, string>
 */
final class KeywordBag implements IteratorAggregate, Countable
{
    /**
     * Keyword storage
     * @var string[]
     */
    private $keywords;

    /**
     * KeywordBag constructor.
     * @param string[] $keywords
     */
    public function __construct(array $keywords = [])
    {
        $this->keywords = $keywords;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->keywords);
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->keywords);
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        return $this->keywords;
    }

    /**
     * @param string[] $keywords
     */
    public function add(array $keywords = []): void
    {
        $this->keywords = array_merge($this->keywords, $keywords);
    }

    public function remove(string $word): bool
    {
        if ($this->has($word)) {
            unset($this->keywords[$word]);
            return true;
        }
        return false;
    }

    public function clear(): void
    {
        $this->keywords = [];
    }

    public function has(string $word): bool
    {
        return array_key_exists($word, $this->keywords);
    }

    public function get(string $word, string $default = '4.0.0'): string
    {
        if ($this->has($word)) {
            $version = $this->keywords[$word];

            if (substr_count($version, '.') < 2) {
                // version number in X.Y.Z format (bugfix part is always zero)
                $version .= '.0';
            }
            return $version;
        }
        return $default;
    }
}

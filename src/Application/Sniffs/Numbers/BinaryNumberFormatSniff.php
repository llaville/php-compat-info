<?php declare(strict_types=1);

/**
 * Binary Number Format (with 0b prefix) since PHP 5.4
 *
 * @link https://www.php.net/manual/en/migration54.new-features.php
 *
 * @see tests/Sniffs/BinaryNumberFormatSniffTest
 */

namespace Bartlett\CompatInfo\Application\Sniffs\Numbers;

use Bartlett\CompatInfo\Application\Sniffs\SniffAbstract;

use PhpParser\Node;

use function mb_strlen;
use function substr_compare;

/**
 * @since Release 5.4.0
 */
final class BinaryNumberFormatSniff extends SniffAbstract
{
    /** @var array<int, mixed> */
    private $tokens;

    /**
     * {@inheritDoc}
     */
    public function enterSniff(): void
    {
        parent::enterSniff();
        $this->tokens = $this->visitor->getTokens();
    }

    /**
     * {@inheritDoc}
     */
    public function leaveNode(Node $node)
    {
        if (!$this->isBinaryNumberFormat($node)) {
            return null;
        }
        $this->updateNodeElementVersion($node, $this->attributeKeyStore, ['ext.name' => 'core', 'ext.min' => '5.4.0', 'php.min' => '5.4.0']);
        return null;
    }

    private function isBinaryNumberFormat(Node $node): bool
    {
        $i = $node->getAttribute('startTokenPos');

        if ($node instanceof Node\Scalar\LNumber && isset($this->tokens[$i][1])) {
            return (substr_compare($this->tokens[$i][1], '0b', 0, 2, true) === 0);
        }
        return false;
    }
}

<?php declare(strict_types=1);

/**
 * Null Coalescing Operator (??) available since PHP 7.0.0alpha1
 *
 * @link https://wiki.php.net/rfc/isset_ternary
 * @link https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.coalesce
 *
 * @see tests/Sniffs/NullCoalesceOperatorSniffTest
 */

namespace Bartlett\CompatInfo\Application\Sniffs\Operators;

use Bartlett\CompatInfo\Application\Sniffs\SniffAbstract;

use PhpParser\Node;

/**
 * @since Release 5.4.0
 */
final class NullCoalesceOperatorSniff extends SniffAbstract
{
    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        if (!$node instanceof Node\Expr\BinaryOp\Coalesce) {
            return null;
        }

        $this->updateNodeElementVersion($node, $this->attributeKeyStore, ['php.min' => '7.0.0alpha1']);
        return null;
    }
}

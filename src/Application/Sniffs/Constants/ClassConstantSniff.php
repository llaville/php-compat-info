<?php declare(strict_types=1);

/**
 * Class constants
 *
 * @link https://www.php.net/manual/en/language.oop5.constants.php
 *
 * @see tests/Sniffs/ClassConstantSniffTest
 */

namespace Bartlett\CompatInfo\Application\Sniffs\Constants;

use Bartlett\CompatInfo\Application\Sniffs\SniffAbstract;

use PhpParser\Node;

/**
 * @since Release 5.4.0
 */
final class ClassConstantSniff extends SniffAbstract
{
    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        if (!$node instanceof Node\Stmt\ClassConst) {
            return null;
        }

        $this->updateNodeElementVersion($node, $this->attributeKeyStore, ['php.min' => '4.0.0']);
        return null;
    }
}

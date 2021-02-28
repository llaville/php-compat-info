<?php declare(strict_types=1);

/**
 * Report use of magic methods
 *
 * @link https://www.php.net/manual/en/language.oop5.magic.php
 *
 * @see tests/Sniffs/MagicMethodsSniffTest
 */

namespace Bartlett\CompatInfo\Application\Sniffs\Classes;

use Bartlett\CompatInfo\Application\Sniffs\SniffAbstract;

use PhpParser\Node;

/**
 * @since Release 5.4.0
 */
final class MagicMethodsSniff extends SniffAbstract
{
    /** @var string[] */
    private $mm503;
    /** @var string[] */
    private $mm506;
    /** @var string[] */
    private $mm501;

    /**
     * {@inheritDoc}
     */
    public function enterSniff(): void
    {
        parent::enterSniff();

        $this->mm501 = ['__isset', '__unset', '__set_state'];
        $this->mm503 = ['__callStatic', '__invoke'];
        $this->mm506 = ['__debugInfo'];
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        if (!$node instanceof Node\Stmt\ClassMethod) {
            return null;
        }

        $name = (string) $node->name;

        if (in_array($name, $this->mm501)) {
            $min = '5.1.0';
        } elseif (in_array($name, $this->mm503)) {
            $min = '5.3.0';
        } elseif (in_array($name, $this->mm506)) {
            $min = '5.6.0';
        } else {
            return null;
        }

        $this->updateNodeElementVersion($node, $this->attributeKeyStore, ['php.min' => $min]);
        return null;
    }
}

<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\Sniffs\Keywords;

use Bartlett\CompatInfo\Application\Sniffs\KeywordBag;
use Bartlett\CompatInfo\Application\Sniffs\SniffAbstract;

use PhpParser\Node;

/**
 * You cannot use any of the following words to name classes, interfaces or traits.
 *
 * @link https://www.php.net/manual/en/reserved.other-reserved-words.php
 * @link https://www.php.net/manual/en/migration70.incompatible.php#migration70.incompatible.other.classes
 * @link https://wiki.php.net/rfc/reserve_more_types_in_php_7
 *
 * @see tests/Sniffs/KeywordReservedSniffTest
 * @since Class available since Release 5.4.0
 */
final class ReservedSniff extends SniffAbstract
{
    /**
     * {@inheritDoc}
     */
    public function enterSniff(): void
    {
        parent::enterSniff();

        /**
         * The following words cannot be used to name a class, interface or trait,
         * and they are also prohibited from being used in namespaces.
         */
        $this->forbiddenNames = new KeywordBag(
            [
                'bool' => '7.0',
                'int' => '7.0',
                'float' => '7.0',
                'string' => '7.0',
                'null' => '7.0',
                'true' => '7.0',
                'false' => '7.0',
                'void', '7.1',
                'iterable' => '7.1',
                'object' => '7.2',
            ]
        );

        /**
         * Furthermore, the following names should not be used.
         * Although they will not generate an error in PHP 7.0,
         * they are reserved for future use and should be considered deprecated.
         */
        $this->forbiddenNames->add(
            [
                'resource' => '7.0',
                'mixed' => '7.0',
                'numeric' => '7.0',
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        $this->contextIdentifier = $this->getNameContext($node);
        if (empty($this->contextIdentifier)) {
            return null;
        }

        if ($node instanceof Node\Stmt\Namespace_) {
            $this->contextCallback = [$this, 'enter' . str_replace('_', '', $node->getType())];
        } elseif ($node instanceof Node\Stmt\ClassLike) {
            $this->contextCallback = [$this, 'enterObject'];
        } else {
            $this->contextCallback = null;
        }

        if (!empty($this->contextCallback) && is_callable($this->contextCallback)) {
            call_user_func($this->contextCallback, $node);
        }

        return null;
    }

    /**
     * Checks that reserved word can not be used as class, interface or trait names
     *
     * @param Node\Stmt\ClassLike $node
     * @return void
     * @see enterNode
     */
    private function enterObject(Node\Stmt\ClassLike $node): void
    {
        $this->checkForbiddenNames($node, $this->contextIdentifier);
    }

    /**
     * Checks that reserved word is prohibited from being used in part of a namespace
     *
     * @param Node\Stmt\Namespace_ $node
     * @return void
     * @see enterNode
     */
    private function enterStmtNamespace(Node\Stmt\Namespace_ $node): void
    {
        $namespaceParts = explode('\\', $this->contextIdentifier);

        foreach ($namespaceParts as $namespacePart) {
            $this->checkForbiddenNames($node, $namespacePart);
        }
    }
}

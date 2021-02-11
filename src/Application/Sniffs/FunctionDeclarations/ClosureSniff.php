<?php declare(strict_types=1);

/**
 * Closures are available since PHP 5.3
 *
 * $this in closure allowed since PHP 5.4
 * Anonymous functions may be declared statically since PHP 5.4
 * Anonymous functions allowed since PHP 5.3
 *
 * @link https://wiki.php.net/rfc/closures
 * @link https://wiki.php.net/rfc/closures/object-extension
 * @link https://www.php.net/manual/en/functions.anonymous.php
 * @link https://www.php.net/manual/en/functions.anonymous.php#functions.anonymous-functions.static
 *
 * @see tests/Sniffs/ClosureSniffTest
 */

namespace Bartlett\CompatInfo\Application\Sniffs\FunctionDeclarations;

use Bartlett\CompatInfo\Application\Sniffs\SniffAbstract;

use PhpParser\Node;

/**
 * @since Release 5.4.0
 */
final class ClosureSniff extends SniffAbstract
{
    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        $parent = $node->getAttribute($this->attributeParentKeyStore);

        if (!$parent instanceof Node\Expr\Closure) {
            // not in Closure context
            return null;
        }

        // Base minimum version 5.3.0 is already initialized by the "VersionResolverVisitor"

        if ($node instanceof Node\Expr\Variable && is_string($node->name)) {
            $keyword = $node->name;
        } elseif ($node instanceof Node\Expr\ClassConstFetch && $node->class instanceof Node\Name) {
            $keyword = (string) $node->class;
        } elseif ($node instanceof Node\Expr\StaticCall && $node->class instanceof Node\Name) {
            $keyword = (string) $node->class;
        } else {
            return null;
        }

        $name = strtolower($keyword);

        if (in_array($name, ['this', 'self', 'parent', 'static'])) {
            // Use of $this | self | parent | static inside a closure is allowed since PHP 5.4
            $this->updateNodeElementVersion($parent, $this->attributeKeyStore, ['php.min' => '5.4.0']);
        }
        return null;
    }
}

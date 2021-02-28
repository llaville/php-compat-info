<?php declare(strict_types=1);

/**
 * NameResolver to initialize full qualified name on new attribute specified by option
 * - nodeAttributeNamespacedName
 * rather than use the `namespacedName` property of each node
 */

namespace Bartlett\CompatInfo\Application\PhpParser\NodeVisitor;

use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeVisitor\NameResolver;

use function property_exists;

/**
 * @since Release 5.4.0
 */
final class NameResolverVisitor extends NameResolver
{
    /** @var string */
    private $attributeParentKey;

    /** @var string */
    private $attributeNamespacedName;

    /**
     * NameResolverVisitor constructor.
     *
     * @param ErrorHandler|null $errorHandler
     * @param array<string, string> $options
     */
    public function __construct(ErrorHandler $errorHandler = null, array $options = [])
    {
        $this->attributeParentKey = $options['nodeAttributeParentKeyStore'] ?? 'bartlett.parent';
        $this->attributeNamespacedName = $options['nodeAttributeNamespacedName'] ?? 'bartlett.name';
        unset($options['nodeAttributeParentKeyStore'], $options['nodeAttributeNamespacedName']);
        parent::__construct($errorHandler, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        if ($node instanceof Node\Stmt\Namespace_) {
            $node->setAttribute($this->attributeNamespacedName, (string) $node->name);
        } elseif ($node instanceof Node\Stmt\ClassLike) {
            if ($node instanceof Node\Stmt\Class_ && null === $node->name) {
                // anonymous class
                $name = $this->concatParentRecursive($node);
                $node->setAttribute($this->attributeNamespacedName, (string) $name);
            } else {
                $node->setAttribute($this->attributeNamespacedName, (string) $node->namespacedName);
            }
        } elseif ($node instanceof Node\FunctionLike
            || $node instanceof Node\Const_
            || $node instanceof Node\Expr\Yield_
            || $node instanceof Node\Expr\YieldFrom
        ) {
            $name = $this->concatParentRecursive($node);
            $node->setAttribute($this->attributeNamespacedName, (string) $name);
        }
        return null;
    }

    /**
     * Resolves the full parent path recursively.
     *
     * @param Node $node Start context
     * @return null|Name Fully Qualified
     */
    private function concatParentRecursive(Node $node): ?Name
    {
        $parentNode = $node->getAttribute($this->attributeParentKey);
        if ($parentNode instanceof Node) {
            $parent = $this->concatParentRecursive($parentNode);
        } else {
            $parent = null;
        }
        if ($node instanceof Node\Expr\Closure) {
            $name = sprintf(
                'closure-%d-%d',
                $node->getAttribute('startLine', 0),
                $node->getAttribute('endLine', 0)
            );
        } elseif ($node instanceof Node\Expr\ArrowFunction) {
            $name = sprintf(
                'fn-%d-%d',
                $node->getAttribute('startLine', 0),
                $node->getAttribute('endLine', 0)
            );
        } elseif ($node instanceof Node\Stmt\Class_ && null === $node->name) {
            $name = sprintf(
                'anonymous-class-%d-%d',
                $node->getAttribute('startLine', 0),
                $node->getAttribute('endLine', 0)
            );
        } elseif ($node instanceof Node\Expr\Yield_
            || $node instanceof Node\Expr\YieldFrom
        ) {
            $name = sprintf(
                'generator-%d-%d',
                $node->getAttribute('startLine', 0),
                $node->getAttribute('endLine', 0)
            );
        } elseif ($node instanceof Node\Stmt\Namespace_) {
            $name = $node->name;
        } elseif (property_exists($node, 'name')) {
            $name = (string) $node->name;
        } else {
            $name = null;
        }
        return Name::concat($parent, $name);
    }
}

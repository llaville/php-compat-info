<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\PhpParser\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\NodeVisitorAbstract;

use function array_pop;
use function array_shift;

/**
 * @since Release 5.4.0
 */
final class ParentContextVisitor extends NodeVisitorAbstract
{
    /** @var Node[] */
    private $stack = [];

    /** @var string */
    private $attributeParentKey;

    /**
     * Constructs a parent path resolution visitor.
     *
     * Options:
     *  * nodeAttributeParentKeyStore (default "bartlett.parent"): An attribute will be added
     *    on each classes, interfaces, traits, functions, closures, arrow functions or properties to store the parent node.
     *
     * @param array<string, string> $options
     */
    public function __construct(array $options = [])
    {
        $this->attributeParentKey = $options['nodeAttributeParentKeyStore'] ?? 'bartlett.parent';
    }

    /**
     * {@inheritDoc}
     */
    public function beforeTraverse(array $nodes)
    {
        $this->stack = [];

        $filter = function (Node $node) {
            return ($node instanceof Node\Stmt\Namespace_);
        };

        $visitor = new FindingVisitor($filter);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        $foundNodes = $visitor->getFoundNodes();

        if (0 === count($foundNodes)) {
            /** @var Node\Stmt[] $newNodes */
            $newNodes = $nodes;
            // global namespace is not explicitly specified in source code ... we will add it
            if ($nodes[0] instanceof Node\Stmt\Declare_) {
                $declare = array_shift($newNodes);
                $newNodes = [$declare, new Node\Stmt\Namespace_(null, $newNodes)];
            } else {
                $newNodes = [new Node\Stmt\Namespace_(null, $newNodes)];
            }
            return $newNodes;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        if (!empty($this->stack)) {
            $node->setAttribute($this->attributeParentKey, $this->stack[count($this->stack) - 1]);
        }

        if ($node instanceof Node\Stmt\Namespace_
            || $node instanceof Node\Stmt\ClassLike
            || $node instanceof Node\FunctionLike
            || $node instanceof Node\Stmt\Property
            || $node instanceof Node\Stmt\ClassConst
        ) {
            $this->stack[] = $node;
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_
            || $node instanceof Node\Stmt\ClassLike
            || $node instanceof Node\FunctionLike
            || $node instanceof Node\Stmt\Property
            || $node instanceof Node\Stmt\ClassConst
        ) {
            array_pop($this->stack);
        }
        return null;
    }
}

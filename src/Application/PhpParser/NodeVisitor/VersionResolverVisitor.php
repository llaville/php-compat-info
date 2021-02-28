<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\PhpParser\NodeVisitor;

use Bartlett\CompatInfo\Application\Collection\ReferenceCollectionInterface;
use Bartlett\CompatInfo\Application\DataCollector\VersionUpdater;
use Bartlett\CompatInfo\Application\PhpParser\Node\Name\ClassFullyQualified;
use Bartlett\CompatInfo\Application\PhpParser\Node\Name\InterfaceFullyQualified;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class VersionResolverVisitor extends NodeVisitorAbstract
{
    /** @var string */
    private $attributeKey;

    /** @var string */
    private $attributeNamespacedName;

    /** @var ReferenceCollectionInterface<string, array> */
    private $references;

    use VersionUpdater;

    /**
     * Constructs a version resolution visitor.
     *
     * Options:
     *  * nodeAttributeKeyStore (default "bartlett.compatibility_analyser"): An attribute will be added
     *    on each namespaces, classes, interfaces, traits, methods, functions, closures or arrow functions
     *    to store the minimum version required.
     *
     * @param ReferenceCollectionInterface<string, array> $referenceCollection
     * @param array<string, string> $options
     */
    public function __construct(ReferenceCollectionInterface $referenceCollection, array $options = [])
    {
        $this->references = $referenceCollection;
        $this->attributeKey = $options['nodeAttributeKeyStore'] ?? 'bartlett.data_collector';
        $this->attributeNamespacedName = $options['nodeAttributeNamespacedName'] ?? 'bartlett.name';
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        $currentVersions = self::$php4;

        if ($node instanceof Node\Stmt\Namespace_) {
            if (null === $node->name) {
                $currentVersions['ext.name'] = 'core';
            } else {
                $currentVersions['php.min'] = '5.3.0';
            }

        } elseif ($node instanceof Node\Stmt\Class_) {
            if (null !== $node->extends) {
                $versions = $this->resolveClassVersions($node->extends, 'classes');
                $this->updateElementVersion($currentVersions, $versions);
            }

            foreach ($node->implements as $interface) {
                $versions = $this->resolveClassVersions($interface, 'interfaces');
                $this->updateElementVersion($currentVersions, $versions);
                // identify interface for FilterVisitor
                $interface->setAttribute($this->attributeNamespacedName, $interface);
                $this->updateNodeElementVersion($interface, $this->attributeKey, $versions);
            }

        } elseif ($node instanceof Node\Stmt\Interface_) {
            foreach ($node->extends as $interface) {
                $versions = $this->resolveClassVersions($interface, 'interfaces');
                $this->updateElementVersion($currentVersions, $versions);
            }

        } elseif ($node instanceof Node\Stmt\Trait_) {
            $currentVersions['php.min'] = '5.4.0';

        } elseif ($node instanceof Node\FunctionLike) {
            if ($node instanceof Node\Expr\ArrowFunction) {
                // @link https://www.php.net/manual/en/functions.arrow.php
                $currentVersions['php.min'] = '7.4.0';
            } elseif ($node instanceof Node\Expr\Closure) {
                $currentVersions['php.min'] = '5.3.0';
            } elseif ($node instanceof Node\Stmt\Function_) {
                if ($node->namespacedName->isQualified()) {
                    $currentVersions['php.min'] = '5.3.0';
                } else {
                    $currentVersions['php.min'] = '4.0.0';
                }
            }

            $currentVersions = $this->handleParameters($node, $currentVersions);

        } else {
            // do nothing on other nodes
            return null;
        }

        $currentVersions['declared'] = true;
        $node->setAttribute($this->attributeKey, $currentVersions);

        return null;
    }

    /**
     * Handles all arguments of function like.
     *
     * @param Node\FunctionLike $node
     * @param array<string, string> $currentVersions
     * @return array<string, string>
     */
    private function handleParameters(Node\FunctionLike $node, array $currentVersions): array
    {
        foreach ($node->getParams() as $param) {
            if ($param->type instanceof Node\UnionType) {
                $this->updateElementVersion($currentVersions, ['php.min' => '8.0.0']);
                continue;
            }

            if (null !== $param->type) {
                $group = $this->resolveTypeVersions($param->type);
                $attributes = $param->type->getAttributes();

                $nullableType = $param->type instanceof Node\NullableType;

                $name = (string) ($nullableType ? $param->type->type : $param->type);

                if ('interfaces' === $group) {
                    $newType = new InterfaceFullyQualified($name, $attributes);
                } else {
                    $newType = new ClassFullyQualified($name, $attributes);
                }
                $param->type = $nullableType ? new Node\NullableType((string) $newType) : $newType;

                $this->updateElementVersion($currentVersions, $attributes[$this->attributeKey]);
            }
        }

        return $currentVersions;
    }

    /**
     * Checks for extension's class or interface in database if referenced.
     *
     * @param Node\Name $name
     * @param string $group
     * @return array<string, string>
     */
    private function resolveClassVersions(Node\Name $name, string $group): array
    {
        // not yet known, try to detect for non user elements
        $versions = $this->references->find($group, (string) $name);
        // arg.max is useless (nonsense) in this context
        unset($versions['arg.max']);

        if ('user' == $versions['ext.name']) {
            if ('interfaces' === $group) {
                $min = $name->isQualified() ? '5.3.0' : '5.0.0';
                $this->updateVersion($min,$versions['php.min']);
            } else {
                if ($name->isUnqualified()) {
                    // PHP4 syntax (global namespace)
                    $min = '4.0.0';
                    $this->updateVersion($min,$versions['php.min']);
                }
            }
        }
        return $versions;
    }

    /**
     * Checks for extension's class or interface, used as type hinting, if referenced in database.
     *
     * @param Node $node
     * @return string
     */
    private function resolveTypeVersions(Node $node): string
    {
        $groups = ['interfaces', 'classes'];
        $name = $node instanceof Node\NullableType ? $node->type : $node;

        if ($name instanceof Node\Name || $name instanceof Node\Identifier) {
            $name = (string) $name;
        }

        if (!is_string($name)) {
            // Node undetected, returns default
            return 'classes';
        }

        foreach ($groups as $group) {
            // not yet known, try to detect for non user elements
            $versions = $this->references->find($group, $name);
            // arg.max is useless (nonsense) in this context
            unset($versions['arg.max']);

            if ('user' !== $versions['ext.name']) {
                break;
            }
            // remove the previously cached response before trying new attempt
            $this->references->remove($name);
        }

        $node->setAttribute($this->attributeKey, $versions);

        return $group;
    }
}

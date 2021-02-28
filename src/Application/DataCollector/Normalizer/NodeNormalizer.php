<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\DataCollector\Normalizer;

use Bartlett\CompatInfo\Application\PhpParser\Node\Name\ClassFullyQualified;
use Bartlett\CompatInfo\Application\PhpParser\Node\Name\InterfaceFullyQualified;

use PhpParser\Node;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @since Release 5.4.0
 */
final class NodeNormalizer implements NormalizerInterface
{
    /** @var null|string */
    private $name;

    /** @var string */
    private $attributeNamespacedName;

    /**
     * {@inheritDoc}
     * @param array<string, string> $context
     * @return null|array<string, mixed>
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $node = $object;
        $this->attributeNamespacedName = $context['nodeAttributeNamespacedName'] ?? 'bartlett.name';

        if (!$this->supportsNormalization($node, $format)) {
            return null;
        }

        if (null === $this->name) {
            // indirect called cannot be resolved and will be ignored
            return null;
        }

        $attributeParentKey = $context['nodeAttributeParentKeyStore'] ?? 'bartlett.parent';
        $attributeKey = $context['nodeAttributeKeyStore'] ?? 'bartlett.data_collector';

        $parents = [];
        $parentNode = $node->getAttribute($attributeParentKey, null);

        while ($parentNode instanceof Node) {
            if ($parentNode->hasAttribute($this->attributeNamespacedName)) {
                $parentId = (string) $parentNode->getAttribute($this->attributeNamespacedName);
            } elseif (property_exists($parentNode, 'name')) {
                $parentId = (string) $parentNode->name;
            } else {
                $parentId = false;
            }
            if ($parentId !== false) {
                $parents[] = [$this->getType($parentNode) => $parentId];
            }

            $parentNode = $parentNode->getAttribute($attributeParentKey, null);
        }

        return [
            'type' => $this->getType($node),
            'id' => $this->name,
            'versions' => $node->getAttribute($attributeKey, null),
            'parents' => $parents,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        if ($data instanceof Node\Expr\New_) {
            $this->name = $data->class instanceof Node\Stmt\Class_ ? 'class' : (string) $data->class;
        } elseif ($data instanceof Node\Stmt\Declare_) {
            $keys = [];
            foreach ($data->declares as $declare) {
                $keys[] = (string) $declare->key;
            }
            $this->name = implode(', ', $keys);
        } elseif ($data instanceof Node\Stmt\Use_) {
            $keys = [];
            foreach ($data->uses as $use) {
                $keys[] = (string) $use->name;
            }
            $this->name = implode(', ', $keys);
        } elseif ($data instanceof Node\Stmt\TraitUse) {
            $keys = [];
            foreach ($data->traits as $trait) {
                $keys[] = (string) $trait;
            }
            $this->name = implode(', ', $keys);
        } elseif ($data instanceof Node\Stmt\Property) {
            $props = [];
            foreach ($data->props as $prop) {
                $props[] = (string) $prop->name;
            }
            $this->name = implode(', ', $props);
        } elseif ($data instanceof Node\Stmt\Const_ || $data instanceof Node\Stmt\ClassConst) {
            $keys = [];
            foreach ($data->consts as $const) {
                $keys[] = (string) $const->getAttribute($this->attributeNamespacedName);
            }
            $this->name = implode(', ', $keys);
        } elseif ($data instanceof Node\Scalar\MagicConst) {
            $this->name = $data->getName();
        } elseif ($data instanceof Node\Expr\BinaryOp\Coalesce
            || $data instanceof Node\Expr\BinaryOp\Pow
            || $data instanceof Node\Expr\AssignOp\Pow
            || $data instanceof Node\Expr\Ternary
            || $data instanceof Node\Expr\BinaryOp\Spaceship
            || $data instanceof Node\Expr\Empty_
            || $data instanceof Node\Scalar\LNumber
        ) {
            $this->name = '';
        } elseif ($data instanceof InterfaceFullyQualified
            || $data instanceof ClassFullyQualified
        ) {
            $this->name = (string) $data;
        } elseif ($data instanceof Node\Expr\StaticCall) {
            if ($data->class instanceof Node\Name) {
                if ($data->name instanceof Node\Scalar\String_) {
                    // Class::{expr}() syntax
                    $this->name = sprintf("%s::{'%s'}", $data->class, $data->name->value);
                } else {
                    $this->name = (string) $data->getAttribute($this->attributeNamespacedName);
                }
            }
        } elseif ($this->isConstantDefineExpression($data)) {
            $name = $data->args[0]->value;
            if ($name instanceof Node\Scalar\String_) {
                $this->name = $name->value;
            } else {
                // indirect constant naming is not resolved
                $this->name = null;
            }
        } elseif ($data->hasAttribute($this->attributeNamespacedName)) {
            $this->name = (string) $data->getAttribute($this->attributeNamespacedName);
        } elseif (property_exists($data, 'name')) {
            $this->name = (string) $data->name;
        } else {
            return false;
        }

        return true;
    }

    private function getType(Node $node): string
    {
        if ($this->isConstantDefineExpression($node)) {
            // consider define constant as const keyword
            $type = 'Stmt_Const';
        } else {
            $type = $node->getType();
        }

        $groups = [
            'namespaces' => ['Stmt_Namespace'],
            'classes' => ['Stmt_Class', 'Name_ClassFullyQualified', 'Expr_New'],
            'interfaces' => ['Stmt_Interface', 'Name_InterfaceFullyQualified', 'Name_FullyQualified'],
            'traits' => ['Stmt_Trait', 'Stmt_TraitUse'],
            'methods' => ['Stmt_ClassMethod', 'Expr_MethodCall', 'Expr_StaticCall'],
            'generators' => ['Expr_Yield', 'Expr_YieldFrom'],
            'functions' => ['Stmt_Function', 'Expr_Closure', 'Expr_FuncCall', 'Expr_ArrowFunction'],
            'constants' => [
                'Const',
                'Stmt_Const',
                'Stmt_ClassConst',
                'Expr_ConstFetch',
                'Scalar_MagicConst_Class',
                'Scalar_MagicConst_Dir',
                'Scalar_MagicConst_File',
                'Scalar_MagicConst_Function',
                'Scalar_MagicConst_Line',
                'Scalar_MagicConst_Method',
                'Scalar_MagicConst_Namespace',
                'Scalar_MagicConst_Trait',
            ],
            'directives' => ['Stmt_Declare'],
        ];

        foreach ($groups as $group => $types) {
            if (in_array($type, $types)) {
                return $group;
            }
        }
        return $type;
    }

    private function isConstantDefineExpression(Node $node): bool
    {
        return ($node instanceof Node\Expr\FuncCall
            && $node->name instanceof Node\Name
            && strcasecmp((string) $node->name, 'define') === 0
        );
    }
}

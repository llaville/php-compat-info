<?php declare(strict_types=1);

/**
 * A bridge to original PhpParser\NodeDumper
 * that allows to dump attributes
 */

namespace Bartlett\CompatInfo\Application\PhpParser;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;

/**
 * @since Release 6.0.0
 */
final class NodeDumper extends \PhpParser\NodeDumper
{
    private $dumpAttributes;
    private $dumpComments;
    private $dumpPositions;
    private $code;

    /**
     * Constructs a NodeDumper.
     *
     * Additional supported options:
     *  * bool dumpAttributes: Whether attributes should be dumped.
     *
     * @param array $options Options (see description)
     */
    public function __construct(array $options = [])
    {
        $this->dumpAttributes = !empty($options['dumpAttributes']);
        parent::__construct($options);
    }

    /**
     * {@inheritDoc}
     */
    public function dump($node, string $code = null): string
    {
        $this->code = $code;
        if ($this->dumpAttributes) {
            return $this->dumpRecursiveAttributes($node)  ;
        }
        return $this->dumpRecursive($node);
    }

    /**
     * {@inheritDoc}
     */
    public function dumpPosition(Node $node)
    {
        return parent::dumpPosition($node);
    }

    /**
     * @param array|Node|Comment $node Node or array to dump
     * @return string
     */
    protected function dumpRecursiveAttributes($node): string
    {
        if ($node instanceof Node) {
            $r = $node->getType();
            if ($this->dumpPositions && null !== $p = $this->dumpPosition($node)) {
                $r .= $p;
            }
            $r .= '(';

            foreach ($node->getSubNodeNames() as $key) {
                $r .= "\n    " . $key . ': ';

                $value = $node->$key;
                if (null === $value) {
                    $r .= 'null';
                } elseif (false === $value) {
                    $r .= 'false';
                } elseif (true === $value) {
                    $r .= 'true';
                } elseif (is_scalar($value)) {
                    if ('flags' === $key || 'newModifier' === $key) {
                        $r .= $this->dumpFlags($value);
                    } elseif ('type' === $key && $node instanceof Include_) {
                        $r .= $this->dumpIncludeType($value);
                    } elseif ('type' === $key
                        && ($node instanceof Use_ || $node instanceof UseUse || $node instanceof GroupUse)) {
                        $r .= $this->dumpUseType($value);
                    } else {
                        $r .= $value;
                    }
                } else {
                    $r .= str_replace("\n", "\n    ", $this->dumpRecursiveAttributes($value));
                }
            }

            if ($this->dumpComments && $comments = $node->getComments()) {
                $r .= "\n    comments: " . str_replace("\n", "\n    ", $this->dumpRecursiveAttributes($comments));
            }

            if ($this->dumpAttributes && $attrs = $node->getAttributes()) {
                $attributes = $node->getAttributes();

                foreach ($attributes as $key => $attr) {
                    if ($attr instanceof Node) {
                        $attributes[$key] = ['type' => $attr->getType()];
                    } elseif (!is_array($attr)) {
                        $attributes[$key] = [gettype($attr) => $attr];
                        continue;
                    }
                }
                $r .= "\n    attributes: " . str_replace("\n", "\n    ", $this->dumpRecursiveAttributes($attributes));
            }
        } elseif (is_array($node)) {
            $r = 'array(';

            foreach ($node as $key => $value) {
                $r .= "\n    " . $key . ': ';

                if (null === $value) {
                    $r .= 'null';
                } elseif (false === $value) {
                    $r .= 'false';
                } elseif (true === $value) {
                    $r .= 'true';
                } elseif (is_scalar($value)) {
                    $r .= $value;
                } else {
                    $r .= str_replace("\n", "\n    ", $this->dumpRecursiveAttributes($value));
                }
            }
        } elseif ($node instanceof Comment) {
            return $node->getReformattedText();
        } else {
            throw new \InvalidArgumentException('Can only dump nodes and arrays.');
        }

        return $r . "\n)";
    }
}

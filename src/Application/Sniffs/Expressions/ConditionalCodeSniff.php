<?php declare(strict_types=1);

/**
 * Detect conditional code identified by :
 * - [extension_loaded](https://www.php.net/manual/en/function.extension-loaded.php)
 * - [function_exists](https://www.php.net/manual/en/function.function-exists.php)
 * - [method_exists](https://www.php.net/manual/en/function.method-exists)
 * - [class_exists](https://www.php.net/manual/en/function.class-exists.php)
 * - [interface_exists](https://www.php.net/manual/en/function.interface-exists.php)
 * - [trait_exists](https://www.php.net/manual/en/function.trait-exists.php)
 * - [defined](https://www.php.net/manual/en/function.defined)
 *
 * @see tests/Sniffs/ConditionalCodeSniffTest
 */

namespace Bartlett\CompatInfo\Application\Sniffs\Expressions;

use Bartlett\CompatInfo\Application\Collection\ReferenceCollectionInterface;
use Bartlett\CompatInfo\Application\Sniffs\SniffAbstract;

use PhpParser\Node;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function ltrim;

/**
 * @since Release 5.4.0
 */
final class ConditionalCodeSniff extends SniffAbstract
{
    /** @var string */
    private $opt;

    /** @var string */
    private $group;

    /** @var ReferenceCollectionInterface */
    private $references;

    public function __construct(
        EventDispatcherInterface $compatibilityEventDispatcher,
        ReferenceCollectionInterface $referenceCollection
    ) {
        parent::__construct($compatibilityEventDispatcher);
        $this->references = $referenceCollection;
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        if (!$this->isConditionalCode($node)) {
            return null;
        }
        /** @var Node\Expr\FuncCall $node */

        $versions =
            $this->references->find(
                'functions',
                (string) $node->name,
                (isset($node->args) ? count($node->args) : 0)
            )
        ;

        /** @var Node\Scalar\String_ $arg */
        $arg = $node->args[0]->value;

        $extra = ('methods' === $this->group) ? $arg->value : null;

        $data = $node->getAttribute($this->attributeKeyStore, $versions);
        $data['opt.name'] = ('methods' === $this->group) ? $extra . '\\' . $this->opt : $this->opt;
        $data['opt.group'] = $this->group;
        $data['opt.versions'] = $this->references->find($this->group, $this->opt, 0, $extra);
        $node->setAttribute($this->attributeKeyStore, $data);

        return null;
    }

    /**
     * Checks if node match a conditional code.
     *
     * @param Node $node
     * @return bool
     */
    private function isConditionalCode(Node $node): bool
    {
        if (($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) === false) {
            return false;
        }

        $function = (string) $node->name;

        $conditionalFunctions = [
            'extension_loaded' => 'extensions',
            'function_exists'  => 'functions',
            'method_exists'    => 'methods',
            'class_exists'     => 'classes',
            'interface_exists' => 'interfaces',
            'trait_exists'     => 'traits',
            'defined'          => 'constants',
        ];

        $this->group = $conditionalFunctions[$function] ?? '';
        if (empty($this->group)) {
            // not a conditional function
            return false;
        }

        $name = $node->args[0]->value;

        if (!$name instanceof Node\Scalar\String_) {
            // indirect name cannot be resolved
            return false;
        }
        $this->opt = ltrim($name->value, '\\');

        if ('methods' === $this->group) {
            $name = $node->args[1]->value;
            if (!$name instanceof Node\Scalar\String_) {
                // indirect method name cannot be resolved
                return false;
            }
            $this->opt = $name->value;
        }

        return true;
    }
}

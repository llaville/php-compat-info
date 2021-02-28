<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\PhpParser\NodeVisitor;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @since Release 6.0.0
 */
interface NodeVisitor extends \PhpParser\NodeVisitor
{
    /**
     * @return ArrayCollection<int, mixed>
     */
    public function getCollection(): ArrayCollection;
}

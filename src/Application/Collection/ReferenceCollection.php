<?php declare(strict_types=1);

/**
 * Reference collection that collect information for the compatibility analyser.
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 */

namespace Bartlett\CompatInfo\Application\Collection;

use Bartlett\CompatInfoDb\Domain\Repository\ClassRepository;
use Bartlett\CompatInfoDb\Domain\Repository\ConstantRepository;
use Bartlett\CompatInfoDb\Domain\Repository\FunctionRepository;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;

use function array_pop;
use function array_slice;
use function in_array;
use function strpos;

/**
 * @phpstan-extends AbstractLazyCollection<string, array>
 * @since Release 4.0.0-alpha3
 */
final class ReferenceCollection extends AbstractLazyCollection implements ReferenceCollectionInterface
{
    /** @var ClassRepository  */
    private $classRepository;
    /** @var ConstantRepository  */
    private $constantRepository;
    /** @var FunctionRepository  */
    private $functionRepository;

    /**
     * Creates a new Reference Collection
     *
     * @param ClassRepository $classRepository
     * @param ConstantRepository $constantRepository
     * @param FunctionRepository $functionRepository
     */
    public function __construct(
        ClassRepository $classRepository,
        ConstantRepository $constantRepository,
        FunctionRepository $functionRepository
    ) {
        $this->classRepository = $classRepository;
        $this->constantRepository = $constantRepository;
        $this->functionRepository = $functionRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function find(string $group, string $key, int $argc = 0, ?string $extra = null): array
    {
        $this->initialize();

        if ($this->containsKey($key)) {
            $result = $this->get($key);
        } else {
            if (in_array($group, ['functions', 'methods'])) {
                $function = $this->functionRepository->getFunctionByName($key, $extra);
                if (null === $function) {
                    $result = false;
                } else {
                    $result = [
                        'ext.name'     => $function->getExtensionName(),
                        'ext.min'      => $function->getExtMin(),
                        'ext.max'      => $function->getExtMax(),
                        'php.min'      => $function->getPhpMin(),
                        'php.max'      => $function->getPhpMax(),
                        'parameters'   => $function->getParameters(),
                        'php.excludes' => $function->getExcludes(),
                    ];
                }
            } elseif ('constants' === $group) {
                $constant = $this->constantRepository->getConstantByName($key, $extra);
                if (null === $constant) {
                    $result = false;
                } else {
                    $result = [
                        'ext.name' => $constant->getExtensionName(),
                        'ext.min'  => $constant->getExtMin(),
                        'ext.max'  => $constant->getExtMax(),
                        'php.min'  => $constant->getPhpMin(),
                        'php.max'  => $constant->getPhpMax(),
                    ];
                }
            } elseif (in_array($group, ['classes', 'interfaces'])) {
                $classLike = $this->classRepository->getClassByName($key, ('interfaces' === $group));
                if (null === $classLike) {
                    $result = false;
                } else {
                    $result = [
                        'ext.name' => $classLike->getExtensionName(),
                        'ext.min'  => $classLike->getExtMin(),
                        'ext.max'  => $classLike->getExtMax(),
                        'php.min'  => $classLike->getPhpMin(),
                        'php.max'  => $classLike->getPhpMax(),
                    ];
                }
            } else {
                $result = false;
            }

            if (!$result) {
                // when not found in database, should be a user or an unknown extension element
                if (strpos($key, '\\') === false) {
                    // not qualified
                    $min = '4.0.0';
                } else {
                    $min = '5.3.0';
                }
                $result = [
                    'ext.name' => 'user',
                    'ext.min'  => '',
                    'ext.max'  => '',
                    'php.min'  => $min,
                    'php.max'  => '',
                ];
            }
            // cache to speed-up later uses
            $this->set($key, $result);
        }

        // compute the right version depending on number of arguments used
        if (isset($result['parameters']) && !empty($result['parameters'])) {
            $parameters = array_slice($result['parameters'], 0, $argc);
            if (!empty($parameters)) {
                $result['php.min'] = array_pop($parameters);

                if (in_array($result['ext.name'], ['core', 'standard'])) {
                    $result['ext.min'] = $result['php.min'];
                }
            }
        }
        $result['arg.max'] = $argc;
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    protected function doInitialize(): void
    {
        $this->collection = new ArrayCollection();
    }
}

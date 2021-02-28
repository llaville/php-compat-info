<?php declare(strict_types=1);

/**
 * Collect and analyse metrics of parsing results.
 *
 * PHP version 7
 *
 * @category   PHP
 * @package    PHP_CompatInfo
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @license    https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 * @link       http://bartlett.laurent-laville.org/php-compatinfo/
 */

namespace Bartlett\CompatInfo\Application\Query\Analyser\Compatibility;

use Bartlett\CompatInfo\Application\DataCollector\ErrorHandler\Collecting;
use Bartlett\CompatInfo\Application\DataCollector\ErrorHandler\Throwing;
use Bartlett\CompatInfo\Application\PhpParser\Parser;
use Bartlett\CompatInfo\Application\Profiler\Profile;
use Bartlett\CompatInfo\Application\Query\QueryHandlerInterface;
use Bartlett\CompatInfo\Application\Service\SourceProvider;

use RuntimeException;

/**
 * @since Release 6.0.0
 */
final class GetCompatibilityHandler implements QueryHandlerInterface
{
    /** @var Parser  */
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function __invoke(GetCompatibilityQuery $query): Profile
    {
        $dataSource = $query->getSource();
        $source = realpath($dataSource);
        if (false === $source) {
            throw new RuntimeException(sprintf('No data source match (%s)', $dataSource));
        }

        // create compatible nikic/php-parser Throwing and Collecting objects
        $errorHandler = $query->isStopOnFailure() ? new Throwing() : new Collecting();

        $finder = SourceProvider::getFinder($source);

        return $this->parser->parse($source, $finder, $errorHandler);
    }
}

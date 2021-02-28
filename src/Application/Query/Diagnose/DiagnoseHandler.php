<?php declare(strict_types=1);

/**
 * Handler to diagnose common errors in current platform.
 *
 * PHP version 7
 *
 * @category   PHP
 * @package    PHP_CompatInfo
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @license    https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 * @link       http://bartlett.laurent-laville.org/php-compatinfo/
 */

namespace Bartlett\CompatInfo\Application\Query\Diagnose;

use Bartlett\CompatInfo\Application\Query\QueryHandlerInterface;
use Bartlett\CompatInfoDb\Application\Query\Diagnose\DiagnoseHandler as CompatInfoDbDiagnoseHandler;
use Bartlett\CompatInfoDb\Application\Query\Diagnose\DiagnoseQuery as CompatInfoDbDiagnoseQuery;
use Bartlett\CompatInfoDb\Infrastructure\RequirementsInterface;

/**
 * @since Release 6.0.0
 */
final class DiagnoseHandler implements QueryHandlerInterface
{
    /** @var CompatInfoDbDiagnoseHandler */
    private $innerHandler;

    public function __construct(CompatInfoDbDiagnoseHandler $handler)
    {
        $this->innerHandler = $handler;
    }

    public function __invoke(DiagnoseQuery $query): RequirementsInterface
    {
        $handler = $this->innerHandler;
        return $handler(new CompatInfoDbDiagnoseQuery($query->getDatabaseConnection()));
    }
}

<?php declare(strict_types=1);

/**
 * Common Class TestCase
 *
 * @link https://phpunit.readthedocs.io/en/9.3/writing-tests-for-phpunit.html
 */

namespace Bartlett\CompatInfo\Tests;

use Bartlett\CompatInfo\Application\Analyser\CompatibilityAnalyser;
use Bartlett\CompatInfo\Application\Query\Analyser\Compatibility\GetCompatibilityQuery;
use Bartlett\CompatInfo\Application\Query\QueryBusInterface;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Exception;
use function reset;

/**
 * @since Release 5.4.0, 6.0.0
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected static $fixtures;
    protected static $analyserId;

    /**
     * Sets up the shared fixture.
     *
     * @return void
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        self::$fixtures = __DIR__ . DIRECTORY_SEPARATOR
            . 'fixtures' . DIRECTORY_SEPARATOR
        ;

        self::$analyserId = CompatibilityAnalyser::class;
    }

    /**
     * Execute a single test case and return metrics
     *
     * @param string $dataSource
     * @return array
     * @throws Exception
     */
    protected function executeAnalysis(string $dataSource): array
    {
        $compatibilityQuery = new GetCompatibilityQuery(self::$fixtures . $dataSource, false);

        /** @var ContainerBuilder $container */
        $container = require dirname(__DIR__) . '/config/container.php';
        $queryBus = $container->get(QueryBusInterface::class);

        $profile = $queryBus->query($compatibilityQuery);
        $data = $profile->getData();
        return reset($data);
    }
}

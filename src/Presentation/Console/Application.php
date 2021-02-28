<?php declare(strict_types=1);

/**
 * The CompatInfo CLI version.
 *
 * PHP version 7
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 */

namespace Bartlett\CompatInfo\Presentation\Console;

use PackageVersions\Versions;

use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Phar;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use function basename;
use function explode;
use function sprintf;
use function strpos;
use function substr_count;

/**
 * @since Release 4.0.0-alpha3+1, 6.0.0
 */
class Application extends SymfonyApplication implements ApplicationInterface
{
    /**
     * @var string
     * @link http://patorjk.com/software/taag/#p=display&f=Standard&t=phpCompatInfo
     */
    protected static $logo = "        _            ____                            _   ___        __
  _ __ | |__  _ __  / ___|___  _ __ ___  _ __   __ _| |_|_ _|_ __  / _| ___
 | '_ \| '_ \| '_ \| |   / _ \| '_ ` _ \| '_ \ / _` | __|| || '_ \| |_ / _ \
 | |_) | | | | |_) | |__| (_) | | | | | | |_) | (_| | |_ | || | | |  _| (_) |
 | .__/|_| |_| .__/ \____\___/|_| |_| |_| .__/ \__,_|\__|___|_| |_|_|  \___/
 |_|         |_|                        |_|

";

    /** @var ContainerInterface  */
    private $container;

    /**
     * Application constructor.
     *
     * @param string $version (optional) auto-detect
     */
    public function __construct(string $version = 'UNKNOWN')
    {
        if ('UNKNOWN' === $version) {
            // composer or git outside world strategy
            $version = self::VERSION;
        } elseif (substr_count($version, '.') === 2) {
            // release is in X.Y.Z format
        } else {
            // composer or git strategy
            $version = Versions::getVersion('bartlett/php-compatinfo');
            list($ver, ) = explode('@', $version);

            if (strpos($ver, 'dev') === false) {
                $version = $ver;
            }
        }
        parent::__construct(self::NAME, $version);
    }

    /**
     * {@inheritDoc}
     * @return void
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getHelp(): string
    {
        return '<comment>' . static::$logo . '</comment>' . parent::getHelp();
    }

    /**
     * {@inheritDoc}
     */
    public function getLongVersion(): string
    {
        return sprintf(
            '<info>%s</info> version <comment>%s</comment> DB version <comment>%s</comment>',
            $this->getName(),
            $this->getVersion(),
            \Bartlett\CompatInfoDb\Presentation\Console\ApplicationInterface::VERSION
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultInputDefinition(): InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(
            new InputOption(
                'profile',
                null,
                InputOption::VALUE_NONE,
                'Display timing and memory usage information'
            )
        );
        $definition->addOption(
            new InputOption(
                'progress',
                null,
                InputOption::VALUE_NONE,
                'Show progress bar'
            )
        );
        $definition->addOption(
            new InputOption(
                'output',
                null,
                InputOption::VALUE_OPTIONAL,
                'Write results to file'
            )
        );
        $definition->addOption(
            new InputOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'Display debugging information'
            )
        );
        if (Phar::running()) {
            $definition->addOption(
                new InputOption(
                    'manifest',
                    null,
                    InputOption::VALUE_NONE,
                    'Show which versions of dependencies are bundled'
                )
            );
        } else {
            $definition->addOption(
                new InputOption(
                    'config',
                    'c',
                    InputOption::VALUE_REQUIRED,
                    'Read configuration from PHP file'
                )
            );
        }
        return $definition;
    }

    /**
     * {@inheritDoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        if (null === $input) {
            if ($this->container->has(InputInterface::class)) {
                $input = $this->container->get(InputInterface::class);
            } else {
                $input = new ArgvInput();
            }
        }

        if (null === $output) {
            if ($this->container->has(OutputInterface::class)) {
                $output = $this->container->get(OutputInterface::class);
            } else {
                $output = new ConsoleOutput();
            }
        }

        $configFile = $input->getParameterOption('-c');
        if (false === $configFile) {
            $configFile = $input->getParameterOption('--config');
        }
        if (false !== $configFile) {
            $containerBuilder = new ContainerBuilder();
            try {
                $loader = new PhpFileLoader($containerBuilder, new FileLocator(dirname($configFile)));
                $loader->load(basename($configFile));
            } catch (FileLocatorFileNotFoundException $e) {
                $output = new ConsoleOutput();
                $this->renderThrowable($e, $output);
                return Command::FAILURE;
            }
            $containerBuilder->compile(); // mandatory or the sniffCollection won't be populated
            $this->setContainer($containerBuilder);
        }

        return parent::run($input, $output);
    }
}

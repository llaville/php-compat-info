<?php declare(strict_types=1);

/**
 * Analyse a data source to find out requirements.
 *
 */

namespace Bartlett\CompatInfo\Presentation\Console\Command;

use Bartlett\CompatInfo\Application\Analyser\CompatibilityAnalyser;
use Bartlett\CompatInfo\Application\Profiler\Profile;
use Bartlett\CompatInfo\Application\Query\Analyser\Compatibility\GetCompatibilityQuery;
use Bartlett\CompatInfo\Presentation\Console\Style;

use Bartlett\CompatInfo\Presentation\Console\StyleInterface;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

use function array_key_exists;
use function array_unique;
use function count;
use function current;
use function dirname;
use function in_array;
use function ksort;
use function sprintf;
use function str_replace;
use function strpos;
use function substr;
use function ucfirst;
use function version_compare;
use const PHP_EOL;

/**
 * @since Release 6.0.0
 */
final class AnalyserCommand extends AbstractCommand implements CommandInterface
{
    public const NAME = 'analyser:run';

    /** @var array<string, array> */
    private $metrics;

    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Analyse a data source to find out requirements')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Path to the data source'
            )
            ->addOption(
                'stop-on-failure',
                null,
                null,
                'Stop execution upon first error generated during lexing, parsing or some other operation'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $compatibilityQuery = new GetCompatibilityQuery(
            $input->getArgument('source'),
            $input->hasOption('stop-on-failure')
        );

        try {
            $analysis = $this->queryBus->query($compatibilityQuery);
        } catch (HandlerFailedException $e) {
            $io = new Style($input, $output);
            $io->error($e->getMessage());
            return self::FAILURE;
        }

        if ($output->isDebug()) {
            var_dump($analysis);
        } elseif ($analysis instanceof Profile) {
            $this->printReport(new Style($input, $output), current($analysis->getData()));
        }

        return self::SUCCESS;
    }

    /**
     * @param StyleInterface $output
     * @param array<string, array> $response
     */
    private function printReport(StyleInterface $output, array $response): void
    {
        if (empty($response)) {
            // No reports printed if there are no metrics.
            $output->warning('No metrics.');
            return;
        }

        $output->title('Compatibility Analyser');

        $output->section('Data Source Analysed');

        $directories = [];
        $files = $response['files'];
        $errors = $response['errors'];

        foreach ($files as $file) {
            $directories[] = dirname($file);
        }
        $directories = array_unique($directories);

        // print Data Source summaries
        if (count($files) > 0) {
            $output->columns(
                count($directories),
                'Directories                                 %10d'
            );
            $output->columns(
                count($files),
                'Files                                       %10d'
            );
            $output->columns(
                count($errors),
                'Errors                                      %10d'
            );
        }

        if (count($errors)) {
            $output->caution(
                sprintf(
                    'Found %d error%s, while parsing data source',
                    count($errors),
                    count($errors) > 1 ? 's' : ''
                )
            );

            foreach ($errors as $msg) {
                $text = sprintf(
                    '<error>!</error> %s',

                    $msg
                );
                $output->text($text);
            }
        }

        $this->metrics = $metrics = $response[CompatibilityAnalyser::class];

        $groups = [
            'extensions',
            'namespaces',
            'interfaces', 'traits', 'classes',
            'generators',
            'functions',
            'constants',
            'conditions',
        ];
        foreach ($groups as $section) {
            $this->formatSection($section, $output);
        }

        if (!array_key_exists('versions', $metrics)
            || empty($metrics['versions'])
        ) {
            return;
        }

        $min = sprintf('PHP %s (min)', $metrics['versions']['php.min']);

        if (empty($metrics['versions']['php.max'])) {
            $max = '';
        } else {
            $max = sprintf(', PHP %s (max)', $metrics['versions']['php.max']);
        }

        $style = 'php';
        $style = $output->getFormatter()->hasStyle($style) ? $style : 'comment';

        $output->success(sprintf('Requires %s%s', $min, $max));
    }

    private function formatSection(string $section, StyleInterface $io): void
    {
        $length = ('classes' == $section) ? -2 : -1;
        $title  = substr($section, 0, $length);

        $args = $this->metrics[$section] ?? [];

        if (empty($args)) {
            $io->note(sprintf('No %s found', $title));
            return;
        }

        $versions = [
            'ext.name' => 'user',
            'ext.min'  => '',
            'ext.max'  => '',
            'ext.all'  => '',
            'php.min'  => '4.0.0',
            'php.max'  => '',
            'php.all'  => '',
        ];
        // compute global versions of the $section
        foreach ($args as $name => $base) {
            if (isset($base['optional'])) {
                // do not compute conditional elements
                continue;
            }
            foreach ($base as $id => $version) {
                if (!in_array(substr($id, -3), array('min', 'max', 'all'))
                    || 'arg.max' == $id
                ) {
                    continue;
                }
                if (null !== $version && version_compare($version, $versions[$id], 'gt')) {
                    $versions[$id] = $version;
                }
            }
        }
        $phpRequired = self::php($versions);

        $io->section(sprintf('%s Analysis', ucfirst($section)));

        $rows = [];
        ksort($args);

        foreach ($args as $arg => $versions) {
            //if ($arg == 'var_dump') var_dump($versions);

            $flags = isset($versions['optional']) ? 'C' : ' ';

            if (in_array($section, ['classes', 'interfaces', 'traits'])) {
                if ('user' == $versions['ext.name']
                    && ($versions['declared'] ?? false) === false
                ) {
                    $flags .= 'U';
                }
            }

            $row = [
                $flags,
                $arg,
                isset($versions['ext.name']) ? $versions['ext.name'] : '',
                self::ext($versions),
                self::php($versions),
            ];
            /*
                for reference:show command,
                tell us if there are some PHP versions excluded
             */
            if (!empty($versions['php.excludes'])) {
                $row[0] = 'W';
            }
            $rows[] = $row;

            if (in_array($section, ['classes', 'interfaces', 'traits'])
                && $io->isVerbose()
                && !in_array($arg, ['parent', 'self', 'static'])
            ) {
                foreach ($this->metrics['methods'] as $method => $version) {
                    if (strpos($method, "$arg\\") === 0) {
                        $flags = isset($version['optional']) ? 'C' : ' ';
                        $rows[] = [
                            $flags,
                            sprintf('<info>function</info> %s', str_replace("$arg\\", '', $method)),
                            isset($version['ext.name']) ? $version['ext.name'] : '',
                            self::ext($version),
                            self::php($version),
                        ];
                    }
                }
            }
        }

        $headers = ['  ', ucfirst($title), 'REF', 'EXT min/Max', 'PHP min/Max'];

        $footers = [
            '',
            sprintf('<info>Total [%d]</info>', count($args)),
            '',
            '',
            sprintf('<info>%s</info>', $phpRequired)
        ];
        $rows[] = new TableSeparator();
        $rows[] = $footers;

        $io->table($headers, $rows);
    }

    /**
     * @param array<string> $domain
     * @return string
     */
    private function ext(array $domain): string
    {
        return empty($domain['ext.max'])
            ? $domain['ext.min']
            : $domain['ext.min'] . ' => ' . $domain['ext.max']
            ;
    }

    /**
     * @param array<string> $domain
     * @return string
     */
    private function php(array $domain): string
    {
        return empty($domain['php.max'])
            ? $domain['php.min']
            : $domain['php.min'] . ' => ' . $domain['php.max']
            ;
    }
}

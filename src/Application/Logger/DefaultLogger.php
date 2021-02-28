<?php declare(strict_types=1);

/**
 * Default PSR3 compatible logger.
 *
 * PHP version 7
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 */

namespace Bartlett\CompatInfo\Application\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

use DateTime;
use function array_search;
use function call_user_func;
use function error_log;
use function get_class;
use function gettype;
use function is_callable;
use function is_null;
use function is_object;
use function is_scalar;
use function method_exists;
use function sprintf;
use function strpos;
use function strtoupper;
use function strtr;

/**
 * @since Release 5.4.0, 6.0.0
 */
class DefaultLogger extends AbstractLogger
{
    /** @var string[]  */
    private static $levels = [
        100 => 'debug',
        200 => 'info',
        250 => 'notice',
        300 => 'warning',
        400 => 'error',
        500 => 'critical',
        550 => 'alert',
        600 => 'emergency',
    ];

    /** @var string  */
    private $destination;
    /** @var string  */
    private $channel;
    /** @var string  */
    private $level;
    /** @var DefaultLogger|object  */
    private $handler;
    /** @var callable[]  */
    private $processors;

    /**
     * Initialize the default log handler
     *
     * @param string $stream     The stream path
     * @param string $name       The logging channel
     * @param string $level      The minimum logging level
     * @param DefaultLogger|object $handler Optional handler
     * @param callable[] $processors Optional array of processors
     */
    public function __construct(
        string $stream,
        string $name = 'DefaultLoggerChannel',
        string $level = LogLevel::INFO,
        $handler = null,
        array $processors = []
    ) {
        $this->destination = $stream;
        $this->channel = $name;
        $this->level   = array_search($level, self::$levels);

        if (isset($handler)
            && is_object($handler)
            && method_exists($handler, 'handle')
            && is_callable([$handler, 'handle'])
        ) {
            $this->handler = $handler;
        } else {
            $this->handler = $this;
            $processors[] = [$this, 'interpolate'];
        }
        $this->processors = $processors;
    }

    /**
     * Checks whether the given record will be handled by this handler.
     *
     * @param array<string, mixed> $record The record to handle
     *
     * @return bool
     */
    public function isHandling(array $record): bool
    {
        $level = array_search($record['level'], self::$levels);
        return $level >= $this->level;
    }

    /**
     * Adds a log record at an arbitrary level.
     *
     * @param mixed  $level   The log level
     * @param string $message The log message
     * @param array[] $context The log context
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $record = [
            'channel'  => $this->channel,
            'level'    => $level,
            'message'  => $message,
            'context'  => $context,
            'extra'    => [],
            'datetime' => new DateTime(),
        ];

        if ($this->isHandling($record)) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
            $this->handler->handle($record);
        }
    }

    /**
     * Handles a record.
     *
     * @param array<string, mixed> $record The record to handle
     *
     * @return void
     */
    public function handle(array $record): void
    {
        error_log(
            sprintf(
                '%s.%s: %s%s',
                $this->channel,
                strtoupper($record['level']),
                $record['message'],
                PHP_EOL
            ),
            3, $this->destination
        );
    }

    /**
     * Processes a record's message according to PSR-3 rules
     *
     * It replaces {foo} with the value from $context['foo']
     *
     * This code was copied from Monolog\Processor\PsrLogMessageProcessor
     *
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     * @author Jordi Boggiano <j.boggiano@seld.be>
     */
    public function interpolate(array $record): array
    {
        if (false === strpos($record['message'], '{')) {
            return $record;
        }

        $replacements = [];
        foreach ($record['context'] as $key => $val) {
            if (is_null($val)
                || is_scalar($val)
                || (is_object($val) && method_exists($val, "__toString"))
            ) {
                $replacements['{'.$key.'}'] = $val;
            } elseif (is_object($val)) {
                $replacements['{'.$key.'}'] = '[object '. get_class($val).']';
            } else {
                $replacements['{'.$key.'}'] = '['. gettype($val).']';
            }
        }

        $record['message'] = strtr($record['message'], $replacements);

        return $record;
    }
}

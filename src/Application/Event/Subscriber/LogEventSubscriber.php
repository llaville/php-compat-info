<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\Event\Subscriber;

use Bartlett\CompatInfo\Application\Event\BuildEvent;
use Bartlett\CompatInfo\Application\Event\CompleteEvent;
use Bartlett\CompatInfo\Application\Event\ErrorEvent;
use Bartlett\CompatInfo\Application\Event\ProgressEvent;
use Bartlett\CompatInfo\Application\Event\SniffEvent;
use Bartlett\CompatInfo\Application\Event\SuccessEvent;

use Psr\Log\LoggerInterface;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function json_encode;

/**
 * @since Release 5.4.0
 */
final class LogEventSubscriber implements EventSubscriberInterface
{
    /** @var LoggerInterface  */
    private $logger;

    /**
     * LogEventSubscriber constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     * @return array<string, string>
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
            ProgressEvent::class => 'onProgress',
            SuccessEvent::class => 'onSuccess',
            ErrorEvent::class => 'onError',
            CompleteEvent::class => 'onComplete',
            BuildEvent::class => 'onWalkAst',
            SniffEvent::class => 'onSniff',
        ];
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $context = ['command' => $event->getCommand()->getName()];
        $this->logger->info('Start {command} command.', $context);
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $context = ['command' => $event->getCommand()->getName()];
        $this->logger->info('Terminate {command} command.', $context);
    }

    /**
     * @param ProgressEvent<string, string> $event
     */
    public function onProgress(ProgressEvent $event): void
    {
        $this->logger->notice('Start analysis of file "{file}"', $event->getArguments());
    }

    /**
     * @param SuccessEvent<string, string> $event
     */
    public function onSuccess(SuccessEvent $event): void
    {
        $this->logger->info('Analysis of file "{file}" successful.', $event->getArguments());
    }

    /**
     * @param ErrorEvent<string, string> $event
     */
    public function onError(ErrorEvent $event): void
    {
        $this->logger->error('Analysis of file "{file}" failed: {error}', $event->getArguments());
    }

    /**
     * @param CompleteEvent<string, string> $event
     */
    public function onComplete(CompleteEvent $event): void
    {
        $this->logger->notice(
            'Parsing the data source "{source}" is over with {successCount} files proceeded !', $event->getArguments()
        );
    }

    /**
     * @param BuildEvent<string, string> $event
     */
    public function onWalkAst(BuildEvent $event): void
    {
        $context = $event->getArguments();
        $this->logger->debug(
            '{method}'
            . ($context['node'] ? ' ' . $context['node']->getType() : '')
            . ' with {analyser}'
            . ($context['node'] ? ' [' . json_encode($context['node']->getAttributes()) . ']' : ''),
            $context
        );
    }

    /**
     * @param SniffEvent<string, string> $event
     */
    public function onSniff(SniffEvent $event): void
    {
        $context = $event->getArguments();
        $this->logger->debug(
            '{method}'
            . (isset($context['node']) ? ' ' . $context['node']->getType() : '')
            . ' in ' . ($context['sniff'] ?? $context['analyser']),
            $context
        );
    }
}

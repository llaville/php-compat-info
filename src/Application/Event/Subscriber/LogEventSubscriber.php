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
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $context = ['command' => $event->getCommand()->getName()];
        $this->logger->info('Start {command} command.', $context);
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $context = ['command' => $event->getCommand()->getName()];
        $this->logger->info('Terminate {command} command.', $context);
    }

    /**
     * @param ProgressEvent $event
     */
    public function onProgress(ProgressEvent $event)
    {
        $this->logger->notice('Start analysis of file "{file}"', $event->getArguments());
    }

    /**
     * @param SuccessEvent $event
     */
    public function onSuccess(SuccessEvent $event)
    {
        $this->logger->info('Analysis of file "{file}" successful.', $event->getArguments());
    }

    /**
     * @param ErrorEvent $event
     */
    public function onError(ErrorEvent $event)
    {
        $this->logger->error('Analysis of file "{file}" failed: {error}', $event->getArguments());
    }

    /**
     * @param CompleteEvent $event
     */
    public function onComplete(CompleteEvent $event)
    {
        $this->logger->notice(
            'Parsing the data source "{source}" is over with {successCount} files proceeded !', $event->getArguments()
        );
    }

    /**
     * @param BuildEvent $event
     */
    public function onWalkAst(BuildEvent $event)
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
     * @param SniffEvent $event
     */
    public function onSniff(SniffEvent $event)
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

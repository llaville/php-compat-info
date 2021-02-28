<?php declare(strict_types=1);

/**
 * Event subscriber to inject progress display at execution.
 *
 * PHP version 7
 *
 * @category   PHP
 * @package    PHP_CompatInfo
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @license    https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 * @link       http://bartlett.laurent-laville.org/php-compatinfo/
 */

namespace Bartlett\CompatInfo\Application\Event\Subscriber;

use Bartlett\CompatInfo\Application\Event\ProgressEvent;
use Bartlett\CompatInfo\Presentation\Console\Style;
use Bartlett\CompatInfo\Presentation\Console\StyleInterface;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function count;
use function sprintf;

/**
 * @since Release 6.0.0
 */
final class ProgressEventSubscriber implements EventSubscriberInterface
{
    /** @var StyleInterface */
    private $io;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->io = new Style($input, $output);
    }

    /**
     * {@inheritDoc}
     * @return array<string, string>
     */
    public static function getSubscribedEvents()
    {
        return [
            ProgressEvent::class => 'onProgress',
        ];
    }

    /**
     * @param ProgressEvent<string, string> $event
     * @return void
     */
    public function onProgress(ProgressEvent $event): void
    {
        $queue = $event->getQueue();
        if (empty($queue)) {
            return;
        }

        $closure = $event->getClosure();

        $progressBar = $this->io->createProgressBar();

        // @link https://symfony.com/blog/new-in-symfony-4-3-iterable-progress-bars
        foreach ($progressBar->iterate($queue, count($queue)) as $fileInfo) {
            $progressBar->setMessage(sprintf('File %s in progress...', $fileInfo->getRelativePathname()));
            $closure($fileInfo);
        }

        $progressBar->finish();
        $progressBar->clear();
    }
}

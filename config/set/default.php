<?php declare(strict_types=1);

use Bartlett\CompatInfo\Application\Collection\ReferenceCollection;
use Bartlett\CompatInfo\Application\Collection\ReferenceCollectionInterface;
use Bartlett\CompatInfo\Application\Collection\SniffCollection;
use Bartlett\CompatInfo\Application\Logger\DefaultLogger;
use Bartlett\CompatInfo\Application\Profiler\CollectorInterface;
use Bartlett\CompatInfo\Application\Profiler\Profiler;
use Bartlett\CompatInfo\Application\Query\QueryBusInterface;
use Bartlett\CompatInfo\Application\Query\QueryHandlerInterface;
use Bartlett\CompatInfo\Application\Sniffs\SniffInterface;
use Bartlett\CompatInfo\Infrastructure\Bus\Query\MessengerQueryBus;
use Bartlett\CompatInfo\Presentation\Console\Command\CommandInterface;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use Ramsey\Uuid\Uuid;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Messenger\Command\DebugCommand;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * Build the Container with default parameters and services
 *
 * @link https://symfony.com/doc/current/components/dependency_injection.html#avoiding-your-code-becoming-dependent-on-the-container
 *
 * @param ContainerConfigurator $containerConfigurator
 * @return void
 */
return static function (ContainerConfigurator $containerConfigurator): void
{
    $containerConfigurator->import(dirname(__DIR__,2) . '/vendor/bartlett/php-compatinfo-db/config/set/default.php');
    $containerConfigurator->import(__DIR__ . '/common.php');
    $containerConfigurator->import(__DIR__ . '/../packages/messenger.php');

    $parameters = $containerConfigurator->parameters();

    #$parameters->set('app.log_stream_path', sprintf('/tmp/compatinfo-%s.log', date('YmdHi')));
    $parameters->set('app.log_stream_path', sprintf('/var/log/php/compatinfo-%s.log', date('YmdHi')));
    $parameters->set('app.log_channel', 'App');
    $parameters->set('app.log_level', LogLevel::DEBUG);

    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
    ;

    $services->set(LoggerInterface::class, DefaultLogger::class)
        ->args(['%app.log_stream_path%', '%app.log_channel%', '%app.log_level%'])
    ;

    // @link https://symfony.com/doc/current/service_container/tags.html#autoconfiguring-tags
    $services->instanceof(CommandInterface::class)
        ->tag('console.command')
    ;

    $services->set(QueryBusInterface::class, MessengerQueryBus::class)
        // for unit tests
        ->public()
    ;

    // @link https://symfony.com/doc/current/service_container/tags.html#autoconfiguring-tags
    $services->instanceof(QueryHandlerInterface::class)
        ->tag('messenger.message_handler', ['bus' => 'query.bus'])
    ;

    if (getenv('APP_ENV') === 'dev') {
        $services->set('console.command.messenger_debug', DebugCommand::class)
            ->args([[]])
            ->tag('console.command')
        ;
    }

    $services->set(CollectorInterface::class, Profiler::class)
        ->arg('$token', Uuid::uuid4()->toString())
        // for unit tests
        ->public()
    ;

    // @link https://symfony.com/doc/current/service_container/tags.html#autoconfiguring-tags
    $services->instanceof(SniffInterface::class)
        ->tag('phpcompatinfo.sniff')
    ;

    $services->load('Bartlett\CompatInfo\\', __DIR__ . '/../../src');

    // @link https://symfony.com/doc/current/service_container/tags.html#reference-tagged-services
    $services->set(SniffCollection::class)
        ->arg('$sniffs', tagged_iterator('phpcompatinfo.sniff'))
        // for unit tests
        ->public()
    ;

    $services->set(ReferenceCollectionInterface::class, ReferenceCollection::class);
};

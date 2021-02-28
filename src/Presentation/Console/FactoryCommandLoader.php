<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Presentation\Console;

use Bartlett\CompatInfoDb\Presentation\Console\Command\DiagnoseCommand;
use Bartlett\CompatInfoDb\Presentation\Console\Command\InitCommand;
use Bartlett\CompatInfoDb\Presentation\Console\Command\ReleaseCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader as SymfonyFactoryCommandLoader;

use function get_class;
use function in_array;

/**
 * @since Release 6.0.0
 */
final class FactoryCommandLoader extends SymfonyFactoryCommandLoader implements CommandLoaderInterface
{
    /**
     * FactoryCommandLoader constructor.
     *
     * @param Command[] $commands
     */
    public function __construct(iterable $commands)
    {
        $factories = [];

        $blacklist = [
            DiagnoseCommand::class,
            InitCommand::class,
            ReleaseCommand::class,
        ];

        foreach ($commands as $command) {
            if (in_array(get_class($command), $blacklist)) {
                continue;
            }
            $factories[$command->getName()] = function () use ($command) { return $command; };
        }

        parent::__construct($factories);
    }
}

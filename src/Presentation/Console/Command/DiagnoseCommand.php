<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Presentation\Console\Command;

use Bartlett\CompatInfo\Application\Query\Diagnose\DiagnoseQuery;
use Bartlett\CompatInfo\Application\Query\QueryBusInterface;
use Bartlett\CompatInfoDb\Application\Service\Checker;
use Bartlett\CompatInfoDb\Presentation\Console\Style;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @since Release 6.0.0
 */
final class DiagnoseCommand extends AbstractCommand implements CommandInterface
{
    public const NAME = 'diagnose';

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(QueryBusInterface $queryBus, EntityManagerInterface $entityManager)
    {
        parent::__construct($queryBus);
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Diagnoses the system to identify common errors')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $diagnoseQuery = new DiagnoseQuery($this->entityManager);

        $projectRequirements = $this->queryBus->query($diagnoseQuery);

        $io = new Style($input, $output);

        $checker = new Checker($io);
        $checker->setAppName('PHP CompatInfo');
        $checker->printDiagnostic($projectRequirements);

        return self::SUCCESS;
    }
}

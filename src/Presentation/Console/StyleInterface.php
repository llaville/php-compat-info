<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Presentation\Console;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface as SymfonyStyleInterface;

/**
 * @since Release 6.0.0
 */
interface StyleInterface extends SymfonyStyleInterface, OutputInterface
{
    /**
     * @param mixed $lines
     * @param string $format
     */
    public function columns($lines, string $format): void;

    /**
     * @param int $max
     * @return ProgressBar
     */
    public function createProgressBar(int $max = 0);

    /**
     * @param array<string> $headers
     * @param array<string>|array<TableSeparator> $rows
     * @param string $style default to 'compact' rather than 'symfony-style-guide'
     */
    public function table(array $headers, array $rows, string $style = 'compact'): void;
}

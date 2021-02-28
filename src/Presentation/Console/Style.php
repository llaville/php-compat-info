<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Presentation\Console;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @since Release 6.0.0
 */
final class Style extends SymfonyStyle implements StyleInterface
{
    /**
     * {@inheritDoc}
     */
    public function createProgressBar(int $max = 0)
    {
        $progressBar = parent::createProgressBar($max);

        $formats = [
            'very_verbose' => ' %current%/%max% %percent:3s%% %elapsed:6s% %message%',
            'very_verbose_nomax' => ' %current% %elapsed:6s% %message%',

            'debug' => ' %current%/%max% %percent:3s%% %elapsed:6s% %memory:6s% %message%',
            'debug_nomax' => ' %current% %elapsed:6s% %memory:6s% %message%',
        ];
        foreach ($formats as $name => $format) {
            $progressBar::setFormatDefinition($name, $format);
        }

        $progressBar->setMessage('');
        return $progressBar;
    }

    /**
     * {@inheritDoc}
     */
    public function table(array $headers, array $rows, string $style = 'compact'): void
    {
        $style = clone Table::getStyleDefinition($style);
        $style->setCellHeaderFormat('<info>%s</info>');

        $table = new Table($this);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->setStyle($style);

        $table->render();
        $this->newLine();
    }

    /**
     * {@inheritDoc}
     */
    public function columns($lines, string $format): void
    {
        if (!is_array($lines)) {
            $lines = [$lines];
        }

        foreach ($lines as $args) {
            parent::text(vsprintf($format, [$args]));
        }
    }
}

<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\Analyser;

use Bartlett\CompatInfo\Application\DataCollector\ErrorHandler;
use Bartlett\CompatInfo\Application\Profiler\ProfilerInterface;

use PhpParser\NodeVisitor;

/**
 * @since Release 6.0.0
 */
interface SniffAnalyserInterface extends SniffVisitorInterface, NodeVisitor
{
    public function getProfiler(): ProfilerInterface;

    public function setErrorHandler(ErrorHandler $errorHandler): void;
}

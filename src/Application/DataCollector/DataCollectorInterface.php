<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\DataCollector;

use PhpParser\Error;
use PhpParser\Node;

use Symfony\Component\Finder\SplFileInfo;

/**
 * @since Release 5.4.0
 */
interface DataCollectorInterface
{
    /**
     * Resets a data collector to its initial state.
     */
    public function reset(): void;

    /**
     * Retrieves data collected.
     *
     * @return array[]
     */
    public function getData(): array;

    /**
     * Collects data for the given Node from AST.
     *
     * @param Node[] $nodes
     * @return array<mixed, mixed>
     */
    public function collect(array $nodes): array;

    /**
     * Identify the data collector by a unique name.
     *
     * @param string $name
     * @return DataCollectorInterface
     */
    public function setName(string $name): DataCollectorInterface;

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     */
    public function getName(): string;

    /**
     * Collects file analysed.
     *
     * @param SplFileInfo $file
     */
    public function addFile(SplFileInfo $file): void;

    /**
     * Returns list of files analysed.
     *
     * @return string[]
     */
    public function getFiles(): array;

    /**
     * Collects all errors found.
     *
     * @param Error[] $errors
     */
    public function addErrors(array $errors): void;

    /**
     * Returns list of errors collected.
     *
     * @return string[]
     */
    public function getErrors(): array;
}

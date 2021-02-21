<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\PhpParser;

use Bartlett\CompatInfo\Application\Analyser\SniffAnalyserInterface;
use Bartlett\CompatInfo\Application\Collection\ReferenceCollectionInterface;
use Bartlett\CompatInfo\Application\DataCollector\ErrorHandler;
use Bartlett\CompatInfo\Application\Event\CompleteEvent;
use Bartlett\CompatInfo\Application\Event\ProgressEvent;
use Bartlett\CompatInfo\Application\Event\SuccessEvent;
use Bartlett\CompatInfo\Application\PhpParser\NodeVisitor\NameResolverVisitor;
use Bartlett\CompatInfo\Application\PhpParser\NodeVisitor\ParentContextVisitor;
use Bartlett\CompatInfo\Application\PhpParser\NodeVisitor\VersionResolverVisitor;

use Bartlett\CompatInfo\Application\Profiler\Profile;
use PhpParser\Error;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function file_get_contents;

/**
 * @since Release 5.4.0
 */
class Parser
{
    private $input;
    private $dispatcher;
    private $analyser;
    private $references;
    private $errorHandler;
    private $parser;
    private $lexer;
    private $traverser;
    private $filesProceeded;

    public function __construct(
        InputInterface $input,
        EventDispatcherInterface $compatibilityEventDispatcher,
        SniffAnalyserInterface $compatibilityAnalyser,
        ReferenceCollectionInterface $referenceCollection
    ) {
        $this->input = $input;
        $this->dispatcher = $compatibilityEventDispatcher;
        $this->analyser = $compatibilityAnalyser;
        $this->references = $referenceCollection;
    }

    /**
     * Analyse a data source and return all analyser metrics.
     *
     * @param string $source
     * @param Finder $finder
     * @param ErrorHandler $errorHandler
     *
     * @return Profile
     */
    public function parse(string $source, Finder $finder, ErrorHandler $errorHandler): Profile
    {
        $this->filesProceeded = 0;

        $this->errorHandler = $errorHandler;

        $profiler = $this->analyser->getProfiler();

        $this->lexer = new Emulative([
                'usedAttributes' => [
                    'comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos'
                ]
            ]);
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, $this->lexer);

        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new ParentContextVisitor());
        $this->traverser->addVisitor(new NameResolverVisitor($this->errorHandler));
        $this->traverser->addVisitor(new VersionResolverVisitor($this->references));
        $this->traverser->addVisitor($this->analyser);

        $this->analyser->setUpBeforeVisitor();

        if ($this->input->hasOption('progress') && $this->input->getOption('progress')) {
            $this->dispatcher->dispatch(
                new ProgressEvent(
                    $this,
                    [
                        'source'  => $source,
                        'queue'   => $finder,
                        'closure' => [$this, 'processFile']
                    ]
                )
            );
        } else {
            foreach ($finder as $fileInfo) {
                $this->processFile($fileInfo);
            }
        }

        $this->analyser->tearDownAfterVisitor();

        $this->dispatcher->dispatch(new CompleteEvent($this, ['source' => $source, 'successCount' => $this->filesProceeded]));

        return $profiler->collect();
    }

    /**
     * Callback that analyse one file of the data source
     *
     * @param SplFileInfo $fileInfo
     */
    public function processFile(SplFileInfo $fileInfo)
    {
        $this->dispatcher->dispatch(new ProgressEvent($this, ['file' => $fileInfo->getRelativePathname()]));

        $stmts = $this->parser->parse(
            file_get_contents($fileInfo->getPathname()),
            $this->errorHandler
        );
        if (empty($stmts)) {
            $this->errorHandler->handleError(
                new Error('File has no contents', ['startLine' => 1])
            );
        }

        $this->analyser->setCurrentFile($fileInfo);
        $this->analyser->setErrorHandler($this->errorHandler);
        $this->analyser->setTokens($this->lexer->getTokens());

        $this->traverser->traverse($stmts);

        $this->filesProceeded++;
        $this->dispatcher->dispatch(new SuccessEvent($this, ['file' => $fileInfo->getRelativePathname()]));
    }
}

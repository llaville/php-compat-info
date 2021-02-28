<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\DataCollector;

use Bartlett\CompatInfo\Application\DataCollector\Normalizer\NodeNormalizer;
use Bartlett\CompatInfo\Application\PhpParser\NodeVisitor\FilterVisitor;

use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_merge;
use function array_pop;
use function array_replace;
use function explode;
use function implode;
use function in_array;
use function strpos;

/**
 * @since 5.4.0
 */
final class VersionDataCollector extends DataCollector
{
    /** @var string[] */
    private $dataKeysAllowed = [];

    /** @var array<string, mixed> */
    private $versions = [];

    /**
     * VersionDataCollector constructor.
     * @param string[] $keysAllowed
     */
    public function __construct(array $keysAllowed)
    {
        $visitor = new FilterVisitor(
            new NodeNormalizer()
        );

        parent::__construct($visitor);
        $this->dataKeysAllowed = $keysAllowed;
        $this->reset();
    }

    /**
     * {@inheritDoc}
     */
    public function collect(array $nodes): array
    {
        $elements = parent::collect($nodes);

        foreach ($elements as $element) {
            $group = $element['type'];
            $name = $element['id'];
            $versions = array_replace(self::$php4, $element['versions']);

            if (isset($this->data[$group][$name])) {
                $this->updateElementVersion($this->data[$group][$name], $versions);
            } else {
                if (isset($versions['opt.group'])) {
                    $this->handleCodeWithCondition($name, $versions);
                    unset(
                        $versions['opt.name'],
                        $versions['opt.group'],
                        $versions['opt.versions']
                    );
                } else {
                    $versions['parents'] = $element['parents'];
                }
                $this->data[$group][$name] = $versions;
            }
        }

        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        parent::reset();

        $this->versions = array_filter(self::$php4, function ($key) {
            return (strpos($key, 'php.') !== false);
        }, ARRAY_FILTER_USE_KEY);
        $this->data = array_fill_keys($this->dataKeysAllowed, []);
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        $data = array_merge(
            ['versions' => ['php.min' => '4.0.0', 'php.max' => '']],
            $this->data
        );

        foreach($data as $group => $elements) {
            if (in_array($group, ['versions', 'extensions', 'conditions'])) {
                // skip un-computable elements
                continue;
            }

            foreach ($elements as $name => $versions) {
                $optional = ($versions['optional'] ?? false) === true;

                $extName = $versions['ext.name'];
                if ('user' !== $extName) {
                    $this->updateExtension($extName, $data['extensions'], $versions);
                }

                if ($optional) {
                    if (!in_array($versions['ext.name'], ['core', 'standard', 'user'])) {
                        $data['extensions'][$versions['ext.name']]['optional'] = true;
                    }
                    // do not compute conditional code
                    continue;
                }

                // Updates all parent context elements
                foreach ($versions['parents'] ?? [] as $parent) {
                    $type = key($parent);
                    $id = reset($parent);
                    if (isset($data[$type][$id]) || array_key_exists($id, $data[$type])) {
                        $this->updateElementVersion($data[$type][$id], $versions);
                    }
                }

                // Updates the global versions (only php.min and php.max) of the data source scanned
                unset($versions['ext.name']);
                $this->updateElementVersion($data['versions'], $versions);
            }

            if (!in_array($group, $this->dataKeysAllowed)) {
                // do not keep temporary nodes used by sniffs to detect language features
                unset($data[$group]);
            }
        }

        return $data;
    }

    /**
     * @return array[]
     */
    public function getVersions(): array
    {
        return $this->data['versions'] ?? [];
    }

    /**
     * @return array[]
     */
    public function getExtensions(): array
    {
        return $this->data['extensions'] ?? [];
    }

    /**
     * @return array[]
     */
    public function getNamespaces(): array
    {
        return $this->data['namespaces'] ?? [];
    }

    /**
     * @return array[]
     */
    public function getClasses(): array
    {
        return $this->data['classes'] ?? [];
    }

    /**
     * @return array[]
     */
    public function getInterfaces(): array
    {
        return $this->data['interfaces'] ?? [];
    }

    /**
     * @return array[]
     */
    public function getTraits(): array
    {
        return $this->data['traits'] ?? [];
    }

    /**
     * @return array[]
     */
    public function getMethods(): array
    {
        return $this->data['methods'] ?? [];
    }

    /**
     * @return array[]
     */
    public function getGenerators(): array
    {
        return $this->data['generators'] ?? [];
    }

    /**
     * @return array[]
     */
    public function getFunctions(): array
    {
        return $this->data['functions'] ?? [];
    }

    /**
     * @return array[]
     */
    public function getConstants(): array
    {
        return $this->data['constants'] ?? [];
    }

    /**
     * @return array[]
     */
    public function getDirectives(): array
    {
        return $this->data['directives'] ?? [];
    }

    /**
     * @return array[]
     */
    public function getConditions(): array
    {
        return $this->data['conditions'] ?? [];
    }

    /**
     * @param string $id
     * @param array<string, mixed> $condition
     */
    private function handleCodeWithCondition(string $id, array $condition): void
    {
        $name = $condition['opt.name'];
        $group = $condition['opt.group'];
        $versions = $condition['opt.versions'];

        if (empty($versions['ext.min'])) {
            // when version is empty because NameResolver cannot resolve FQN class name (import namespace not specified)
            // i.e: with vfsStream 1.6.0 ZipArchive is under user namespace `org\bovigo\vfs`
            // @see https://github.com/bovigo/vfsStream/blob/v1.6.0/src/test/php/org/bovigo/vfs/vfsStreamZipTestCase.php#L39
            $versions['ext.min'] = '0.1.0';
            $versions['ext.max'] = $versions['php.max'] = '';
            $versions['php.min'] = '4.0.0';
        }

        $this->data['conditions'][sprintf('%s(%s)', $id, $name)] = $versions;

        if (!isset($this->data[$group][$name])) {
            $this->data[$group][$name] = $versions;
        }
        $this->data[$group][$name]['optional'] = true;

        if ('methods' === $group) {
            $parts = explode('\\', $name);
            array_pop($parts);
            $name = implode('\\', $parts);
            $group = 'classes';

            if (!isset($this->data[$group][$name])) {
                $this->data[$group][$name] = $versions;
            }
            $this->data[$group][$name]['optional'] = true;
        }
    }
}

<?php declare(strict_types=1);

namespace Bartlett\CompatInfo\Application\DataCollector;

use PhpParser\Node;

use function array_merge;

/**
 * @since Release 5.4.0
 */
trait VersionUpdater
{
    /** @var array<string, mixed> */
    protected static $php4 = [
        'ext.name' => 'user',
        'ext.min'  => '',
        'ext.max'  => '',
        'ext.all'  => '',
        'php.min'  => '4.0.0',
        'php.max'  => '',
        'php.all'  => '',
        'matches'  => 0,
        'declared' => false,
    ];

    /**
     * Updates the base version if current ref version is greater
     *
     * @param string $current Current version
     * @param string $base    Base version
     * @return bool
     */
    protected function updateVersion(string $current, string &$base): bool
    {
        if (version_compare($current, $base, 'gt')) {
            $base = $current;
            return true;
        }
        return false;
    }

    /**
     * Updates the version of a specific element.
     *
     * @param array<string, mixed> $target
     * @param array<string, string> $versions
     * @return bool
     */
    protected function updateElementVersion(array &$target, array $versions): bool
    {
        $updated = false;
        $allowedKeys = ['php.min', 'php.max', 'php.all'];

        if (isset($versions['ext.name']) && ($versions['declared'] ?? false) === false && 'user' !== $versions['ext.name']) {
            $allowedKeys = array_merge(['ext.min', 'ext.max', 'ext.all'], $allowedKeys);
            $target['ext.name'] = $versions['ext.name'];
        }

        foreach ($target as $key => &$value) {
            if (!in_array($key, $allowedKeys)) {
                // updates versions part only on above keys
                continue;
            }
            if (null === $value) {
                continue;
            }
            if ($this->updateVersion($versions[$key] ?? '', $value)) {
                $updated = true;
            }
        }
        return $updated;
    }

    /**
     * Updates the version of a specific element in a node of the AST.
     *
     * @param Node $node
     * @param string $attributeKey
     * @param array<string, string> $versions
     */
    protected function updateNodeElementVersion(Node $node, string $attributeKey, array $versions): void
    {
        $currentVersion = $node->getAttribute($attributeKey, self::$php4);
        $this->updateElementVersion($currentVersion, $versions);
        $node->setAttribute($attributeKey, $currentVersion);
    }

    /**
     * Updates versions of an Extension.
     *
     * @param string $name
     * @param array<string, string> $target
     * @param array<string, string> $versions
     * @return bool
     */
    protected function updateExtension(string $name, array &$target, array $versions): bool
    {
        if (!isset($target[$name])) {
            $target[$name] = $versions;
            $target[$name]['ext.name'] = $name;
            return true;
        }

        return $this->updateElementVersion($target[$name], $versions);
    }
}

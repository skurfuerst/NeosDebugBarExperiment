<?php

declare(strict_types=1);

namespace Sandstorm\NeosDebugBar\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Neos\Flow\Annotations as Flow;

/**
 * Collects Neos content cache statistics (hits, misses, uncached segments,
 * writes and flushes) for display in the PHP Debug Bar.
 *
 * This singleton is populated by ContentCacheAspect via AOP interception of
 * Neos\Fusion\Core\Cache\ContentCache and read once per request by
 * DebugBarMiddleware when assembling the debug bar.
 *
 * @Flow\Scope("singleton")
 */
class ContentCacheCollector extends DataCollector implements Renderable
{
    private int $hits = 0;

    /** @var string[] */
    private array $misses = [];

    /** @var string[] */
    private array $uncached = [];

    private int $writes = 0;

    /** @var string[] */
    private array $flushes = [];

    public function recordHit(): void
    {
        $this->hits++;
    }

    public function recordMiss(string $fusionPath): void
    {
        $this->misses[] = $fusionPath;
    }

    public function recordUncached(string $fusionPath): void
    {
        $this->uncached[] = $fusionPath;
    }

    public function recordWrite(): void
    {
        $this->writes++;
    }

    public function recordFlush(string $description): void
    {
        $this->flushes[] = $description;
    }

    public function collect(): array
    {
        return [
            'nb_hits' => $this->hits,
            'nb_misses' => count($this->misses),
            'nb_uncached' => count($this->uncached),
            'nb_writes' => $this->writes,
            'nb_flushes' => count($this->flushes),
            'misses' => $this->misses,
            'uncached' => $this->uncached,
            'flushes' => $this->flushes,
        ];
    }

    public function getName(): string
    {
        return 'neos_content_cache';
    }

    public function getWidgets(): array
    {
        $name = $this->getName();
        return [
            $name => [
                'icon' => 'tags',
                'widget' => 'PhpDebugBar.Widgets.HtmlVariableListWidget',
                'map' => $name,
                'default' => '{}',
            ],
            "$name:badge" => [
                'map' => "$name.nb_misses",
                'default' => 0,
            ],
        ];
    }
}

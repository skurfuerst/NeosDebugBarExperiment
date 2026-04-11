<?php

declare(strict_types=1);

namespace Sandstorm\NeosDebugBar\Aspect;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Sandstorm\NeosDebugBar\DataCollector\ContentCacheCollector;

/**
 * AOP aspect that intercepts Neos\Fusion\Core\Cache\ContentCache method calls
 * to collect content cache statistics for the PHP Debug Bar.
 *
 * All advice methods are gated on the `debuggingActive` pointcut, which checks
 * the Sandstorm.NeosDebugBar.enabled setting at the AOP layer. When the debug
 * bar is disabled this aspect imposes zero runtime overhead.
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class ContentCacheAspect
{
    /**
     * @Flow\Inject
     * @var ContentCacheCollector
     */
    protected $collector;

    /**
     * Active only when the debug bar is enabled.
     *
     * @Flow\Pointcut("setting(Sandstorm.NeosDebugBar.enabled)")
     */
    public function debuggingActive(): void
    {
    }

    /**
     * Intercepts getCachedSegment() to record cache hits and misses.
     *
     * A false return value means no cached entry was found (miss).
     * Any other return value is a successful cache hit.
     *
     * @Flow\Around("method(Neos\Fusion\Core\Cache\ContentCache->getCachedSegment()) && Sandstorm\NeosDebugBar\Aspect\ContentCacheAspect->debuggingActive")
     */
    public function aroundGetCachedSegment(JoinPointInterface $joinPoint): mixed
    {
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);

        if ($result === false) {
            $this->collector->recordMiss($joinPoint->getMethodArgument('fusionPath'));
        } else {
            $this->collector->recordHit();
        }

        return $result;
    }

    /**
     * Intercepts createUncachedSegment() to record fusion paths that are
     * explicitly marked as uncached and re-evaluated on every request.
     *
     * @Flow\AfterReturning("method(Neos\Fusion\Core\Cache\ContentCache->createUncachedSegment()) && Sandstorm\NeosDebugBar\Aspect\ContentCacheAspect->debuggingActive")
     */
    public function afterCreateUncachedSegment(JoinPointInterface $joinPoint): void
    {
        $this->collector->recordUncached($joinPoint->getMethodArgument('fusionPath'));
    }

    /**
     * Intercepts processCacheSegments() to count cache write operations.
     *
     * @Flow\AfterReturning("method(Neos\Fusion\Core\Cache\ContentCache->processCacheSegments()) && Sandstorm\NeosDebugBar\Aspect\ContentCacheAspect->debuggingActive")
     */
    public function afterProcessCacheSegments(JoinPointInterface $joinPoint): void
    {
        $this->collector->recordWrite();
    }

    /**
     * Intercepts flushByTag() to record tag-based cache flushes.
     *
     * @Flow\AfterReturning("method(Neos\Fusion\Core\Cache\ContentCache->flushByTag()) && Sandstorm\NeosDebugBar\Aspect\ContentCacheAspect->debuggingActive")
     */
    public function afterFlushByTag(JoinPointInterface $joinPoint): void
    {
        $this->collector->recordFlush('tag: ' . $joinPoint->getMethodArgument('tag'));
    }

    /**
     * Intercepts flush() to record full cache flushes.
     *
     * @Flow\AfterReturning("method(Neos\Fusion\Core\Cache\ContentCache->flush()) && Sandstorm\NeosDebugBar\Aspect\ContentCacheAspect->debuggingActive")
     */
    public function afterFlush(JoinPointInterface $joinPoint): void
    {
        $this->collector->recordFlush('all');
    }
}

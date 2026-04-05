<?php

declare(strict_types=1);

namespace Sandstorm\NeosDebugBar\ResourceManagement;

use Neos\Flow\ResourceManagement\Storage\PackageStorage;

/**
 * A resource storage that points to the php-debugbar vendor library's assets directory.
 *
 * Analogous to PackageStorage, but instead of reading Resources/Public/ from Flow packages,
 * it exposes the php-debugbar JavaScript/CSS resources for publishing.
 *
 * Flow's FileSystemSymlinkTarget checks instanceof PackageStorage and calls
 * getPublicResourcePaths(), which creates a symlink at:
 *   Web/_Resources/Static/Packages/DebugBar/ -> <php-debugbar>/resources/
 *
 * This makes the assets accessible at /_Resources/Static/Packages/DebugBar/.
 */
class DebugBarAssetStorage extends PackageStorage
{
    /**
     * Override to skip FileSystemStorage's path validation (we don't use a configured path).
     */
    public function initializeObject(): void
    {
    }

    /**
     * Returns the path to the php-debugbar resources directory.
     * The key 'DebugBar' becomes the subdirectory name under _Resources/Static/Packages/.
     *
     * @return array<string, string>
     */
    public function getPublicResourcePaths(): array
    {
        return [
            'DebugBar' => FLOW_PATH_ROOT . 'Packages/Libraries/php-debugbar/php-debugbar/resources',
        ];
    }
}

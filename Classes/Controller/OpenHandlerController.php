<?php

declare(strict_types=1);

namespace Sandstorm\NeosDebugBar\Controller;

use DebugBar\OpenHandler;
use DebugBar\StandardDebugBar;
use DebugBar\Storage\FileStorage;
use GuzzleHttp\Psr7\Response;
use Neos\Flow\Mvc\Controller\ActionController;
use Psr\Http\Message\ResponseInterface;

/**
 * Serves stored debug bar data to the client-side JavaScript.
 *
 * The php-debugbar client JS intercepts XHR/fetch requests, reads the
 * 'phpdebugbar-id' response header, and then fetches the full debug data
 * from this endpoint using the stored request ID.
 *
 * Supports the following operations (via ?op=... query parameter):
 *   - find  — list stored requests (with optional filters/pagination)
 *   - get   — retrieve debug data for a specific request ID
 *   - clear — delete all stored debug data
 *
 * The storage directory must match the one used by DebugBarMiddleware.
 */
class OpenHandlerController extends ActionController
{
    public function handleAction(): ResponseInterface
    {
        $debugbar = new StandardDebugBar();
        $debugbar->setStorage(new FileStorage(FLOW_PATH_TEMPORARY . 'DebugBar/Storage/'));

        $openHandler = new OpenHandler($debugbar);

        // handle() with echo=false and sendHeader=false returns raw JSON string.
        $json = $openHandler->handle($_GET, false, false);

        return new Response(200, ['Content-Type' => 'application/json'], $json);
    }
}

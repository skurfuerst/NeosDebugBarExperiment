<?php

declare(strict_types=1);

namespace Sandstorm\NeosDebugBar\Http;

use DebugBar\Bridge\DoctrineCollector;
use DebugBar\StandardDebugBar;
use DebugBar\Storage\FileStorage;
use GuzzleHttp\Psr7\Response;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sandstorm\NeosDebugBar\Log\FlowLogCollector;

/**
 * PSR-15 middleware that injects the php-debugbar into responses.
 *
 * For HTML responses: injects <link>/<script> tags for external assets
 * (published via DebugBarAssetStorage) and the JS initialisation block.
 *
 * For non-HTML (AJAX) responses: adds a 'phpdebugbar-id' header so the
 * client-side debugbar JS can retrieve the full data from the open handler.
 *
 * Debug data for every request is persisted to Data/Temporary/DebugBar/Storage/
 * as JSON files, enabling historical browsing via the open handler.
 */
class DebugBarMiddleware implements MiddlewareInterface
{
    /**
     * Base URL for published debugbar assets.
     * Corresponds to the symlink created by DebugBarAssetStorage via Flow's
     * FileSystemSymlinkTarget at Web/_Resources/Static/Packages/DebugBar/.
     */
    private const ASSET_BASE_URL = '/_Resources/Static/Packages/DebugBar/';

    /**
     * URL path for the OpenHandlerController action.
     * Must match the route defined in Configuration/Routes.yaml.
     */
    private const OPEN_HANDLER_PATH = '/debugbar/open-handler';

    /**
     * Path relative to FLOW_PATH_TEMPORARY where request data is stored.
     */
    private const STORAGE_RELATIVE_PATH = 'DebugBar/Storage/';

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\InjectConfiguration(path="enabled")
     * @var bool
     */
    protected $enabled;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        // Skip middleware for the open-handler endpoint itself to avoid recursion
        // and spurious storage entries for debugbar's own requests.
        if (str_ends_with($request->getUri()->getPath(), self::OPEN_HANDLER_PATH)) {
            return $handler->handle($request);
        }

        $debugbar = $this->createDebugBar();
        $response = $handler->handle($request);

        $contentType = $response->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'text/html')) {
            return $this->injectDebugBarIntoHtml($response, $debugbar);
        }

        return $this->addDebugBarHeaders($response, $debugbar);
    }

    /**
     * Creates a StandardDebugBar with file-based storage in Data/Temporary.
     * If Doctrine ORM is configured, also adds a DoctrineCollector that logs
     * all SQL queries executed during the request.
     */
    private function createDebugBar(): StandardDebugBar
    {
        $debugbar = new StandardDebugBar();
        $debugbar->setStorage(new FileStorage(FLOW_PATH_TEMPORARY . self::STORAGE_RELATIVE_PATH));

        if ($this->objectManager->isRegistered(\Doctrine\ORM\EntityManagerInterface::class)) {
            /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
            $entityManager = $this->objectManager->get(\Doctrine\ORM\EntityManagerInterface::class);
            try {
                $debugbar->addCollector(new DoctrineCollector($entityManager->getConnection()));
            } catch (\Throwable $e) {
                // Silently skip if the Doctrine connection is not available
            }
        }

        $logCollector = FlowLogCollector::getInstance();
        if ($logCollector !== null) {
            $debugbar->addCollector($logCollector);
        }

        return $debugbar;
    }

    /**
     * Injects external asset tags and the JS initialisation block before </body>.
     *
     * Because setBaseUrl() is called on the renderer, dumpCssAssets() and
     * dumpJsAssets() output <link>/<script src="..."> tags referencing the
     * published files rather than inlining their content.
     */
    private function injectDebugBarIntoHtml(ResponseInterface $response, StandardDebugBar $debugbar): ResponseInterface
    {
        $body = (string)$response->getBody();
        if (!str_contains($body, '</body>')) {
            return $response;
        }

        $renderer = $debugbar->getJavascriptRenderer();
        $renderer->setBaseUrl(self::ASSET_BASE_URL);
        $renderer->setOpenHandlerUrl(self::OPEN_HANDLER_PATH);

        ob_start();
        $renderer->dumpCssAssets();
        $css = ob_get_clean();

        ob_start();
        $renderer->dumpJsAssets();
        $js = ob_get_clean();

        // $css and $js are now <link>/<script src="..."> tags, not raw content.
        // $renderer->render() outputs only the JS initialisation block.
        $injection = $css . $js . $renderer->render();

        $body = str_replace('</body>', $injection . '</body>', $body);

        return (new Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $body
        ))->withoutHeader('Content-Length');
    }

    /**
     * For non-HTML (AJAX) responses: collects and persists debug data, then
     * adds a 'phpdebugbar-id' header to the response.
     *
     * The client-side debugbar JS reads this header and fetches the full data
     * from the open handler URL using the embedded request ID.
     */
    private function addDebugBarHeaders(ResponseInterface $response, StandardDebugBar $debugbar): ResponseInterface
    {
        $driver = new Psr7HttpDriver();
        $debugbar->setHttpDriver($driver);

        // sendDataInHeaders(true) = use open handler mode:
        // triggers collect() + save to FileStorage, then calls
        // $driver->setHeaders(['phpdebugbar-id' => '<request-id>'])
        $debugbar->sendDataInHeaders(true);

        foreach ($driver->getCollectedHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}

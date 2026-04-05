<?php

declare(strict_types=1);

namespace Sandstorm\NeosDebugBar\Http;

use DebugBar\StandardDebugBar;
use GuzzleHttp\Psr7\Response;
use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DebugBarMiddleware implements MiddlewareInterface
{
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

        $response = $handler->handle($request);

        return $this->injectDebugBar($response);
    }

    private function injectDebugBar(ResponseInterface $response): ResponseInterface
    {
        $contentType = $response->getHeaderLine('Content-Type');
        if (!str_contains($contentType, 'text/html')) {
            return $response;
        }

        $body = (string)$response->getBody();
        if (!str_contains($body, '</body>')) {
            return $response;
        }

        $debugbar = new StandardDebugBar();
        $renderer = $debugbar->getJavascriptRenderer();

        ob_start();
        $renderer->dumpCssAssets();
        $css = ob_get_clean();

        ob_start();
        $renderer->dumpJsAssets();
        $js = ob_get_clean();

        $injection = '<style>' . $css . '</style>'
            . '<script>' . $js . '</script>'
            . $renderer->render();

        $body = str_replace('</body>', $injection . '</body>', $body);

        return (new Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $body
        ))->withoutHeader('Content-Length');
    }
}

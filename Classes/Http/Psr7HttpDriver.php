<?php

declare(strict_types=1);

namespace Sandstorm\NeosDebugBar\Http;

use DebugBar\HttpDriverInterface;

/**
 * A PSR-7 compatible HttpDriver for php-debugbar.
 *
 * Instead of calling header() (like PhpHttpDriver does), this driver captures
 * headers into an array so the middleware can attach them to the PSR-7 response.
 *
 * Used when processing AJAX/non-HTML responses: DebugBar::sendDataInHeaders()
 * calls setHeaders() on this driver, which we then read back via getCollectedHeaders()
 * and add to the immutable PSR-7 ResponseInterface.
 */
class Psr7HttpDriver implements HttpDriverInterface
{
    /** @var array<string, string> */
    private array $collectedHeaders = [];

    /**
     * @param array<string, string> $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->collectedHeaders = array_merge($this->collectedHeaders, $headers);
    }

    public function sendHeaders(): void
    {
        // No-op: headers are applied to the PSR-7 response by the middleware.
    }

    public function output(string $data): void
    {
        // No-op: output is handled via the PSR-7 response body.
    }

    /**
     * Returns the headers collected by sendDataInHeaders() / setHeaders().
     *
     * @return array<string, string>
     */
    public function getCollectedHeaders(): array
    {
        return $this->collectedHeaders;
    }
}

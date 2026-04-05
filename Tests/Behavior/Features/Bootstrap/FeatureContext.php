<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Neos\Behat\FlowBootstrapTrait;
use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Client\InternalRequestEngine;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

class FeatureContext implements Context
{
    use FlowBootstrapTrait;

    private Browser $browser;
    private ?ResponseInterface $response = null;

    public function __construct()
    {
        self::bootstrapFlow();
        $this->setupBrowser();
    }

    private function setupBrowser(): void
    {
        $this->browser = new Browser();
        $objectManager = self::getObject(ObjectManagerInterface::class);
        $engine = $objectManager->get(InternalRequestEngine::class);
        $this->browser->setRequestEngine($engine);
    }

    /**
     * @When I request :url
     */
    public function iRequest(string $url): void
    {
        $this->response = $this->browser->request('http://localhost' . $url);
    }

    /**
     * @When I request :url with header :header set to :value
     */
    public function iRequestWithHeader(string $url, string $header, string $value): void
    {
        $this->browser->addAutomaticRequestHeader($header, $value);
        $this->response = $this->browser->request('http://localhost' . $url);
        $this->browser->removeAutomaticRequestHeader($header);
    }

    /**
     * @Then the response status code should be :code
     */
    public function theResponseStatusCodeShouldBe(int $code): void
    {
        Assert::assertNotNull($this->response, 'No response received');
        Assert::assertSame(
            $code,
            $this->response->getStatusCode(),
            sprintf(
                'Expected status %d but got %d. Body: %s',
                $code,
                $this->response->getStatusCode(),
                substr((string)$this->response->getBody(), 0, 500)
            )
        );
    }

    /**
     * @Then the response should contain :text
     */
    public function theResponseShouldContain(string $text): void
    {
        Assert::assertNotNull($this->response, 'No response received');
        $body = (string)$this->response->getBody();
        Assert::assertStringContainsString($text, $body);
    }

    /**
     * @Then the response should not contain :text
     */
    public function theResponseShouldNotContain(string $text): void
    {
        Assert::assertNotNull($this->response, 'No response received');
        $body = (string)$this->response->getBody();
        Assert::assertStringNotContainsString($text, $body);
    }

    /**
     * @Then the response header :name should exist
     */
    public function theResponseHeaderShouldExist(string $name): void
    {
        Assert::assertNotNull($this->response, 'No response received');
        Assert::assertTrue(
            $this->response->hasHeader($name),
            sprintf('Response header "%s" does not exist. Headers: %s', $name, implode(', ', array_keys($this->response->getHeaders())))
        );
    }

    /**
     * @Then the response header :name should not exist
     */
    public function theResponseHeaderShouldNotExist(string $name): void
    {
        Assert::assertNotNull($this->response, 'No response received');
        Assert::assertFalse(
            $this->response->hasHeader($name),
            sprintf('Response header "%s" should not exist but does', $name)
        );
    }

    /**
     * @Then the response Content-Type should contain :type
     */
    public function theResponseContentTypeShouldContain(string $type): void
    {
        Assert::assertNotNull($this->response, 'No response received');
        $contentType = $this->response->getHeaderLine('Content-Type');
        Assert::assertStringContainsString($type, $contentType);
    }

    /**
     * @Then the response body should be valid JSON
     */
    public function theResponseBodyShouldBeValidJson(): void
    {
        Assert::assertNotNull($this->response, 'No response received');
        $body = (string)$this->response->getBody();
        json_decode($body);
        Assert::assertSame(JSON_ERROR_NONE, json_last_error(), 'Response body is not valid JSON: ' . json_last_error_msg());
    }
}

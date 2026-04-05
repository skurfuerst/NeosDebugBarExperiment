<?php

declare(strict_types=1);

namespace Sandstorm\NeosDebugBar\Controller;

use Neos\Flow\Mvc\Controller\ActionController;

class TestController extends ActionController
{
    public function indexAction(): string
    {
        return '<!DOCTYPE html><html><head><title>Debug Bar Test</title></head><body><h1>Debug Bar Test Page</h1><p>If the debug bar works, it should appear below.</p></body></html>';
    }
}

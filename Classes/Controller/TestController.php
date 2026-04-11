<?php

declare(strict_types=1);

namespace Sandstorm\NeosDebugBar\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\PsrSystemLoggerInterface;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

class TestController extends ActionController
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var PsrSystemLoggerInterface
     */
    protected $systemLogger;

    public function indexAction(): string
    {
        if ($this->objectManager->isRegistered(\Doctrine\ORM\EntityManagerInterface::class)) {
            /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
            $entityManager = $this->objectManager->get(\Doctrine\ORM\EntityManagerInterface::class);
            $entityManager->getConnection()->executeQuery('SELECT 1 as debugbar_test');
        }

        $this->systemLogger->debug('debugbar_log_test_message');

        return '<!DOCTYPE html><html><head><title>Debug Bar Test</title></head><body><h1>Debug Bar Test Page</h1><p>If the debug bar works, it should appear below.</p></body></html>';
    }
}

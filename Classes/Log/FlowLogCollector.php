<?php

declare(strict_types=1);

namespace Sandstorm\NeosDebugBar\Log;

use DebugBar\DataCollector\MessagesCollector;
use Neos\Flow\Log\Backend\BackendInterface;

/**
 * Combined Flow logging backend and php-debugbar collector.
 *
 * Registered as a backend in Flow's PsrLoggerFactory configuration, so it
 * receives every log message via append(). Those messages are stored directly
 * in the collector (MessagesCollector), ready to be added to the debug bar.
 *
 * A static $instance reference lets the middleware locate this object after
 * the logger factory has instantiated it — without any static message buffer
 * or transfer step.
 */
class FlowLogCollector extends MessagesCollector implements BackendInterface
{
    private static ?self $instance = null;

    public function __construct()
    {
        parent::__construct('flow_logs');
        self::$instance = $this;
    }

    /**
     * Returns the instance created by PsrLoggerFactory, or null if the
     * backend was never opened (e.g. logging disabled or not yet initialised).
     */
    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    // -------------------------------------------------------------------------
    // BackendInterface
    // -------------------------------------------------------------------------

    public function open(): void {}

    public function close(): void {}

    public function flush(): void {}

    public function append(
        string $message,
        int $severity = LOG_INFO,
        $additionalData = null,
        ?string $packageKey = null,
        ?string $className = null,
        ?string $methodName = null
    ): void {
        $this->addMessage($message, self::severityToLabel($severity));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function severityToLabel(int $severity): string
    {
        return match (true) {
            $severity >= LOG_DEBUG   => 'debug',
            $severity >= LOG_INFO    => 'info',
            $severity >= LOG_NOTICE  => 'notice',
            $severity >= LOG_WARNING => 'warning',
            $severity >= LOG_ERR     => 'error',
            default                  => 'emergency',
        };
    }
}

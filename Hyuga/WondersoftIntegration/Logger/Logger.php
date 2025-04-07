<?php
declare(strict_types=1);

namespace Hyuga\WondersoftIntegration\Logger;

use Hyuga\WondersoftIntegration\Helper\Config;
use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Logger constructor.
     * @param Config $config
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        Config $config,
        string $name,
        array  $handlers = [],
        array  $processors = []
    )
    {
        parent::__construct($name, $handlers, $processors);
        $this->config = $config;
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = []): void
    {
        if ($this->config->isLoggingEnabled()) {
            parent::info($message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = []): void
    {
        if ($this->config->isLoggingEnabled()) {
            parent::error($message, $context);
        }
    }
}

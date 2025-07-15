<?php

declare(strict_types=1);

namespace InpostApiInterview;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class LoggerProvider
{
    private ?Logger $logger = null;

    public function provideForApiConsumer(): Logger
    {
        if (!$this->logger) {
            $this->logger = new Logger('api-consumer');
            $this->logger->pushHandler(
                new RotatingFileHandler('logs/api-consumer.log')
            );
        }

        return $this->logger;
    }
}
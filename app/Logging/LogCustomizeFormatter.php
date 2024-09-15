<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Illuminate\Log\Logger;

/**
 * This class sets up the logger handlers and formatters for logging.
 */
class LogCustomizeFormatter
{
    /**
     * This method sets up the logger handlers and formatters for logging.
     * It iterates through each handler of the provided logger, setting up the formatter
     * and the processor to print the method stack during logging
     *
     * @param Logger $logger The logger instance to setup.
     *
     * @return void
     */
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(new IntrospectionProcessor(env('LOG_LEVEL', 'debug'), ['Illuminate']));
            $handler->setFormatter(new LineFormatter(
                '[%datetime%] %channel%.%level_name%: %message% %context% %extra%' . PHP_EOL
            ));
        }
    }
}

<?php

/*
 * This file is part of the CORS middleware package
 *
 * Copyright (c) 2016 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/cors-middleware
 *
 */

namespace Tuupola\Middleware\Test;

use Psr\Log\LoggerInterface;

class NullLogger implements LoggerInterface
{
    public function emergency($message, array $context = [])
    {
        return null;
    }

    public function alert($message, array $context = [])
    {
        return null;
    }

    public function critical($message, array $context = [])
    {
        return null;
    }

    public function error($message, array $context = [])
    {
        return null;
    }

    public function warning($message, array $context = [])
    {
        return null;
    }

    public function notice($message, array $context = [])
    {
        return null;
    }

    public function info($message, array $context = [])
    {
        return null;
    }

    public function debug($message, array $context = [])
    {
        return null;
    }

    public function log($level, $message, array $context = [])
    {
        return null;
    }
}

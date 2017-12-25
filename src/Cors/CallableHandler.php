<?php

/*
 * This file is part of the CORS middleware package
 *
 * Copyright (c) 2016-2018 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * See also:
 *   https://github.com/tuupola/cors-middleware
 *   https://github.com/neomerx/cors-psr7
 *   https://www.w3.org/TR/cors/
 */

namespace Tuupola\Middleware\Cors;

use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CallableHandler implements RequestHandlerInterface
{
    private $callable;
    private $response;

    public function __construct(callable $callable, ResponseInterface $response)
    {
        $this->callable = $callable;
        $this->response = $response;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $callable = $this->callable;
        return $callable($request, $this->response);
    }
}

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

namespace Tuupola\Cors\Test;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Tuupola\Middleware\Cors;

class CorsTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldBeTrue()
    {
        $this->assertTrue(true);
    }
}

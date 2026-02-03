<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

/**
 * Base Controller class that extends Laravel's Controller
 * with middleware registration support
 */
abstract class Controller extends BaseController
{
    /**
     * Register middleware to be run by the router.
     *
     * @param string|array $middleware
     * @param array $options
     * @return \Illuminate\Routing\ControllerMiddlewareOptions|$this
     */
    public function middleware($middleware, array $options = [])
    {
        return parent::middleware($middleware, $options);
    }
}

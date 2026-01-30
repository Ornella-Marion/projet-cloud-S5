<?php
// Complete Laravel Framework Stubs for Intelephense

namespace Illuminate\Routing {
    class Controller
    {
        /**
         * Register middleware to be run by the router.
         *
         * @param string|array $middleware
         * @param array $options
         * @return \Illuminate\Routing\ControllerMiddlewareOptions|$this
         */
        public function middleware($middleware, array $options = []) {}
    }

    class ControllerMiddlewareOptions
    {
        public function except($methods) {}
        public function only($methods) {}
    }
}

namespace Illuminate\Auth {
    class AuthManager
    {
        public function user() {}
        public function id() {}
        public function check() {}
        public function guest() {}
    }

    class Guard
    {
        public function user() {}
        public function id() {}
        public function check() {}
        public function guest() {}
    }
}

namespace {
    function auth($guard = null) {}
}

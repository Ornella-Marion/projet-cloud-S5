<?php
// PHPStorm Meta for Laravel Facades

namespace PHPSTORM_META {
    // Auth facade
    override(\auth(0), map([
        '' => \Illuminate\Auth\AuthManager::class,
    ]));

    override(\auth('web'), map([
        '' => \Illuminate\Auth\Guard::class,
    ]));
}

// Laravel Facade declarations for PHPStorm
namespace Illuminate\Auth {
    class AuthManager
    {
        /** @return \App\Models\User|null */
        public function user() {}

        /** @return int|null */
        public function id() {}

        /** @return bool */
        public function check() {}

        /** @return bool */
        public function guest() {}
    }

    class Guard
    {
        /** @return \App\Models\User|null */
        public function user() {}

        /** @return int|null */
        public function id() {}

        /** @return bool */
        public function check() {}

        /** @return bool */
        public function guest() {}
    }
}

// Global helper function
namespace {
    /**
     * Get the authentication guard instance or authenticated user
     *
     * @param string|null $guard
     * @return \Illuminate\Auth\AuthManager|\Illuminate\Auth\Guard|\App\Models\User|null
     */
    function auth($guard = null) {}
}

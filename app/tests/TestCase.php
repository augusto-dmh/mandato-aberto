<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Each request in a test starts from fresh scoped bindings, as a request does in production:
     * Inertia keeps the SSR result of a request in a scoped `SsrState`, and a second request in the
     * same test would otherwise be answered with the first one's body.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app?->forgetScopedInstances();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}

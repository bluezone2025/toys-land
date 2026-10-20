<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tests run every request in one process; drop the "/en" URL root an English
     * request leaves behind so test-side url() calls stay unprefixed.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->resetUrlRoot();
        $response = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
        $this->resetUrlRoot();

        return $response;
    }

    protected function resetUrlRoot(): void
    {
        URL::forceRootUrl(null);
        URL::useAssetOrigin(null);
    }
}

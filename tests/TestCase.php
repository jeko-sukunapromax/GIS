<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function ihrisLoginUrl(): string
    {
        return rtrim(config('services.ihris.base_url'), '/')
            .'/'
            .ltrim(config('services.ihris.login_endpoint', 'login'), '/');
    }
}

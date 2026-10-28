<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

/**
 * CSRF protection without the readable XSRF-TOKEN cookie: the store and the
 * admin panel send the token from the page itself.
 */
class PreventRequestForgery extends Middleware
{
    protected $addHttpCookie = false;

    /** MyFatoorah posts payment updates server-to-server, without a session token. */
    protected $except = ['payment/webhook'];
}

<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Cookie;

/**
 * A cookie written with the attribute spelling from RFC 6265 (Secure, HttpOnly,
 * SameSite=Lax). Browsers accept either case, but some security scanners only
 * recognise this form.
 */
class StandardCookie extends Cookie
{
    public static function from(Cookie $cookie, bool $forceSecure): self
    {
        return new self(
            $cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(),
            $cookie->getDomain(), $forceSecure || $cookie->isSecure(), $cookie->isHttpOnly(),
            $cookie->isRaw(), $cookie->getSameSite(), $cookie->isPartitioned(),
        );
    }

    public function __toString(): string
    {
        $names = ['expires' => 'Expires', 'path' => 'Path', 'domain' => 'Domain', 'secure' => 'Secure',
            'httponly' => 'HttpOnly', 'samesite' => 'SameSite', 'partitioned' => 'Partitioned'];

        $cookie = preg_replace_callback('/; ('.implode('|', array_keys($names)).')(?==|;|$)/', fn ($m) => '; '.$names[$m[1]], parent::__toString());

        return preg_replace_callback('/; SameSite=(\w+)/', fn ($m) => '; SameSite='.ucfirst($m[1]), $cookie);
    }
}

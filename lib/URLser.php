<?php

namespace Lib;

class URLser
{
    public static function parse_domain(string $address)
    {
        $address = parse_url($address);
        $domain = $address['host'];
        $subdomain = self::split_domain($address)[0];
        return array($subdomain, $domain);
    }

    private static function split_domain($host, $SLDs = 'co|com|edu|gov|mil|net|org|cz|de|gg')
    {
        $parts = explode('.', $host);
        $index = count($parts) - 1;

        if ($index > 0 && in_array($parts[$index - 1], explode('|', $SLDs)))
            $index--;

        if ($index === 0)
            $index++;

        $subdomain = implode('.', array_slice($parts, 0, $index - 1));
        $domain = $parts[$index - 1];
        $tld = implode('.', array_slice($parts, $index));

        return array($subdomain, $domain, $tld);
    }
}

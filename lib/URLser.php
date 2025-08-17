<?php

namespace Lib;

/*
 * URLser
 * URL parser
 * Functions useful for parsing URLs into tags
 */
class URLser
{
    public static function parse_domain(string $address)
    {
        $parsed = parse_url($address);
        $host = $parsed['host'];
        $parts = self::split_domain($host);
        return array($parts[0], $parts[1]);
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

    private static function file_get_contents_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public static function get_page_name(string $url)
    {
        $html = self::file_get_contents_curl($url);
        preg_match('/<title>(.+)<\/title>/', $html, $matches);
        if (empty($matches))
            return false;
        return $matches[1];
    }
}

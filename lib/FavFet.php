<?php

namespace lib;

class FavFet
{
    private static function file_get_contents_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_ENCODING, '');

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        unset($ch);

        if ($data === false) {
            error_log("cURL Error for $url: $error");
            return false;
        }

        if ($httpCode >= 400) {
            error_log("HTTP Error $httpCode for $url");
            return false;
        }

        if (empty($data) || strlen($data) < 10) {
            error_log("Invalid or empty data for $url");
            return false;
        }

        return ['data' => $data, 'content_type' => $contentType];
    }

    public static function get_favicon_as_base64($domain, $debug = false)
    {
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = trim($domain, '/');

        $faviconUrls = [
            "https://{$domain}/favicon.ico",
            "https://{$domain}/favicon.png",
            "https://{$domain}/apple-touch-icon.png",
            "http://{$domain}/favicon.ico",
            "http://{$domain}/favicon.png"
        ];

        foreach ($faviconUrls as $url) {
            if ($debug)
                echo "Trying: $url\n";

            $result = self::file_get_contents_curl($url);

            if ($result !== false && !empty($result['data'])) {
                $faviconData = $result['data'];
                $serverContentType = $result['content_type'];

                if ($debug) {
                    echo "Found favicon at: $url\n";
                    echo "Server Content-Type: $serverContentType\n";
                    echo "Data length: " . strlen($faviconData) . " bytes\n";
                    echo "First 20 bytes: " . bin2hex(substr($faviconData, 0, 20)) . "\n";
                }

                if (!self::is_valid_image_data($faviconData)) {
                    if ($debug)
                        echo "Invalid image data, skipping...\n";
                    continue;
                }

                $mimeType = self::get_mime_type($faviconData);

                if ($debug)
                    echo "Detected MIME type: $mimeType\n";

                $base64 = base64_encode($faviconData);

                return "data:{$mimeType};base64,{$base64}";
            }
        }

        return false;
    }

    private static function is_valid_image_data($data)
    {
        if (empty($data) || strlen($data) < 10)
            return false;

        $signatures = [
            "\x89\x50\x4E\x47", // PNG
            "\xFF\xD8\xFF",     // JPEG
            "GIF87a",           // GIF
            "GIF89a",           // GIF
            "\x00\x00\x01\x00", // ICO
            "<svg",             // SVG
            "<?xml"             // XML (could be SVG)-
        ];

        $start = substr($data, 0, 10);
        foreach ($signatures as $sig) {
            if (strpos($start, $sig) === 0 || strpos($data, $sig) !== false)
                return true;
        }

        return false;
    }

    private static function get_mime_type($data)
    {
        $signature = substr($data, 0, 8);

        if (substr($signature, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A")
            return 'image/png';
        else if (substr($signature, 0, 3) === "\xFF\xD8\xFF")
            return 'image/jpeg';
        else if (substr($signature, 0, 6) === "GIF87a" || substr($signature, 0, 6) === "GIF89a")
            return 'image/gif';
        else if (substr($signature, 0, 4) === "\x00\x00\x01\x00")
            return 'image/x-icon';
        else if (strpos($data, '<?xml') === 0 || strpos($data, '<svg') !== false)
            return 'image/svg+xml';
        else
            return 'image/x-icon';
    }
}

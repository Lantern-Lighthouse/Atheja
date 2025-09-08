<?php

namespace lib;

class Identicon
{
    public static function generate_from_string(String $string, int $size = 200, int $gridSize = 5)
    {
        $hash = md5($string);

        // Create image
        $image = imagecreate($size, $size);

        // Extract color from hash
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));

        // Allocate colors
        $backgroundColor = imagecolorallocate($image, 240, 240, 240);
        $foregroundColor = imagecolorallocate($image, $r, $g, $b);

        // Calculate cell size
        $cellSize = $size / $gridSize;

        // Generate pattern from hash
        for ($y = 0; $y < $gridSize; $y++) {
            for ($x = 0; $x < ceil($gridSize / 2); $x++) {
                $index = $y * ceil($gridSize / 2) + $x;
                $byte = hexdec(substr($hash, $index % 32, 1));

                if ($byte % 2 === 0) {
                    // Draw rectangle
                    $x1 = $x * $cellSize;
                    $y1 = $y * $cellSize;
                    $x2 = $x1 + $cellSize;
                    $y2 = $y1 + $cellSize;

                    imagefilledrectangle($image, $x1, $y1, $x2, $y2, $foregroundColor);

                    // Mirror horizontally
                    if ($x < floor($gridSize / 2)) {
                        $mirrorX = ($gridSize - 1 - $x) * $cellSize;
                        imagefilledrectangle($image, $mirrorX, $y1, $mirrorX + $cellSize, $y2, $foregroundColor);
                    }
                }
            }
        }

        return $image;
    }

    public static function output_image($string)
    {
        $image = self::generate_from_string($string);
        header('Content-Type: image/png');
        imagepng($image);
        imagedestroy($image);
    }

    public static function save_image($string, $filename)
    {
        $image = self::generate_from_string($string);
        imagepng($image, $filename);
        imagedestroy($image);
    }
}

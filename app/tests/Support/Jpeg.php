<?php

namespace Tests\Support;

/** Synthetic official photos for the tests: no test downloads a real one (share-cards checks). */
final class Jpeg
{
    /** A photographic-looking JPEG (smooth light, soft noise) whose bytes depend on `$seed`. */
    public static function make(int $width, int $height, int $seed = 1, int $quality = 85): string
    {
        return self::$made["{$width}x{$height}:{$seed}:{$quality}"] ??= self::draw($width, $height, $seed, $quality);
    }

    /** @var array<string, string> */
    private static array $made = [];

    private static function draw(int $width, int $height, int $seed, int $quality): string
    {
        $image = imagecreatetruecolor($width, $height);
        mt_srand($seed);
        [$r0, $g0, $b0] = [mt_rand(60, 200), mt_rand(60, 200), mt_rand(60, 200)];
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $dx = ($x - $width / 2) / $width;
                $dy = ($y - $height / 2.6) / $height;
                $light = 1 - min(1, 2.2 * sqrt($dx * $dx + $dy * $dy));
                $n = mt_rand(-6, 6);
                imagesetpixel($image, $x, $y, imagecolorallocate(
                    $image,
                    max(0, min(255, (int) ($r0 * (0.5 + $light / 2)) + $n)),
                    max(0, min(255, (int) ($g0 * (0.5 + $light / 2)) + $n)),
                    max(0, min(255, (int) ($b0 * (0.5 + $light / 2)) + $n)),
                ));
            }
        }
        ob_start();
        imagejpeg($image, null, $quality);

        return (string) ob_get_clean();
    }

    public static function png(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}

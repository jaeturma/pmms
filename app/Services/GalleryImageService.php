<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/** Creates a metadata-stripped, web-sized derivative when GD is available. */
class GalleryImageService
{
    public function optimize(UploadedFile $source): UploadedFile
    {
        if (! function_exists('imagecreatefromjpeg')) {
            return $source;
        }

        $info = @getimagesize($source->getPathname());
        if ($info === false) {
            return $source;
        }

        [$width, $height, $type] = $info;
        $loader = match ($type) {
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG => 'imagecreatefrompng',
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? 'imagecreatefromwebp' : null,
            default => null,
        };
        if ($loader === null) {
            return $source;
        }

        $image = @$loader($source->getPathname());
        if ($image === false) {
            return $source;
        }

        $max = 1600;
        $ratio = min(1, $max / max($width, $height));
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $path = tempnam(sys_get_temp_dir(), 'pmms-gallery-');
        imagewebp($canvas, $path, 82);
        imagedestroy($image);
        imagedestroy($canvas);

        return new UploadedFile($path, pathinfo($source->getClientOriginalName(), PATHINFO_FILENAME).'.webp', 'image/webp', null, true);
    }
}

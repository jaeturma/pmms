<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class AthletePhotoService
{
    public function __construct(private readonly FileUploadService $uploads) {}

    public function store(UploadedFile $file, User $user, string $type): FileUpload
    {
        $field = $type === 'passport' ? 'photo' : 'sports_photo';

        try {
            $source = $this->decode($file);
            $source = $this->orient($source, $file);
            $settings = config("pmms.athlete_photos.{$type}");
            $target = $this->coverCrop($source, (int) $settings['width'], (int) $settings['height']);
            imagedestroy($source);
            $encoded = $this->encodeWithinLimit(
                $target,
                (int) config('pmms.athlete_photos.max_stored_kb') * 1024,
                $field,
            );
            imagedestroy($target);

            $temporary = tempnam(sys_get_temp_dir(), 'pmms-photo-');
            if ($temporary === false || file_put_contents($temporary, $encoded) === false) {
                throw new RuntimeException('Unable to create optimized photo.');
            }

            try {
                $optimized = new UploadedFile($temporary, bin2hex(random_bytes(16)).'.jpg', 'image/jpeg', null, true);
                $upload = $this->uploads->store($optimized, $user, $field);
                $this->writeDerivatives($upload, $encoded);

                return $upload;
            } finally {
                @unlink($temporary);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                $field => __("We couldn't process this photo. Please use a valid JPG, PNG, or WebP image."),
            ]);
        }
    }

    public function delete(FileUpload $upload): void
    {
        foreach (config('pmms.athlete_photos.derivatives') as $variant => $settings) {
            if (! is_string($variant)) {
                continue;
            }
            Storage::disk($upload->disk)->delete($this->variantPath($upload, $variant));
        }
        $this->uploads->delete($upload);
    }

    public function storeDocument(UploadedFile $file, User $user, string $field): FileUpload
    {
        try {
            $source = $this->orient($this->decode($file), $file);
            $maxEdge = (int) config('pmms.athlete_documents.max_long_edge');
            $target = $this->fitWithin($source, $maxEdge, $maxEdge);
            imagedestroy($source);
            $encoded = $this->encodeDocument($target);
            imagedestroy($target);

            $temporary = tempnam(sys_get_temp_dir(), 'pmms-document-');
            if ($temporary === false || file_put_contents($temporary, $encoded) === false) {
                throw new RuntimeException('Unable to create optimized document.');
            }

            try {
                $optimized = new UploadedFile($temporary, bin2hex(random_bytes(16)).'.jpg', 'image/jpeg', null, true);

                return $this->uploads->store($optimized, $user, $field);
            } finally {
                @unlink($temporary);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                $field => __("We couldn't process this document. Please try another copy or reduce the image size."),
            ]);
        }
    }

    private function encodeDocument(GdImage $image): string
    {
        $limit = (int) config('pmms.athlete_documents.preferred_stored_kb') * 1024;
        $minEdge = (int) config('pmms.athlete_documents.min_long_edge');
        $current = $image;
        $ownsCurrent = false;
        $best = '';

        try {
            while (true) {
                foreach ([88, 84, 80, 76, 72, 68, 64] as $quality) {
                    ob_start();
                    imagejpeg($current, null, $quality);
                    $best = (string) ob_get_clean();
                    if (strlen($best) <= $limit) {
                        return $best;
                    }
                }

                $longEdge = max(imagesx($current), imagesy($current));
                if ($longEdge <= $minEdge) {
                    return $best;
                }

                $scale = max($minEdge / $longEdge, 0.9);
                $next = imagescale($current, (int) round(imagesx($current) * $scale), (int) round(imagesy($current) * $scale));
                if (! $next instanceof GdImage) {
                    return $best;
                }
                if ($ownsCurrent) {
                    imagedestroy($current);
                }
                $current = $next;
                $ownsCurrent = true;
            }
        } finally {
            if ($ownsCurrent) {
                imagedestroy($current);
            }
        }
    }

    public function variantPath(FileUpload $upload, string $variant): string
    {
        $info = pathinfo($upload->path);

        return ($info['dirname'] ?? '.').'/'.$info['filename'].'.'.$variant.'.jpg';
    }

    private function decode(UploadedFile $file): GdImage
    {
        $contents = file_get_contents($file->getPathname());
        $image = $contents === false ? false : @imagecreatefromstring($contents);
        if (! $image instanceof GdImage) {
            throw new RuntimeException('Unsupported image data.');
        }

        return $image;
    }

    private function orient(GdImage $image, UploadedFile $file): GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($file->getPathname());
        $degrees = match ($exif['Orientation'] ?? 1) {
            3 => 180, 6 => -90, 8 => 90, default => 0
        };
        if ($degrees === 0) {
            return $image;
        }
        $rotated = imagerotate($image, $degrees, 0);
        if (! $rotated instanceof GdImage) {
            return $image;
        }
        imagedestroy($image);

        return $rotated;
    }

    private function coverCrop(GdImage $source, int $width, int $height): GdImage
    {
        if ($width < 1 || $height < 1) {
            throw new RuntimeException('Photo dimensions must be positive.');
        }
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        abort_if($sourceWidth * $sourceHeight > 60_000_000, 422, 'Photo dimensions are too large to process safely.');
        $scale = max($width / $sourceWidth, $height / $sourceHeight);
        $cropWidth = (int) round($width / $scale);
        $cropHeight = (int) round($height / $scale);
        $target = imagecreatetruecolor($width, $height);
        imagecopyresampled($target, $source, 0, 0, (int) (($sourceWidth - $cropWidth) / 2), (int) (($sourceHeight - $cropHeight) / 2), $width, $height, $cropWidth, $cropHeight);

        return $target;
    }

    private function fitWithin(GdImage $source, int $maxWidth, int $maxHeight): GdImage
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        abort_if($sourceWidth * $sourceHeight > 60_000_000, 422, 'Document dimensions are too large to process safely.');
        $scale = min(1, $maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $target = imagecreatetruecolor($width, $height);
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        return $target;
    }

    private function encodeWithinLimit(GdImage $image, int $limit, string $field): string
    {
        foreach ([86, 80, 74, 68, 60, 52, 44] as $quality) {
            ob_start();
            imagejpeg($image, null, $quality);
            $contents = (string) ob_get_clean();
            if (strlen($contents) <= $limit) {
                return $contents;
            }
        }
        throw ValidationException::withMessages([$field => __('The image could not be reduced to a safe storage size.')]);
    }

    private function writeDerivatives(FileUpload $upload, string $encoded): void
    {
        $source = imagecreatefromstring($encoded);
        if (! $source instanceof GdImage) {
            throw new RuntimeException('Unable to create photo derivatives.');
        }
        foreach (config('pmms.athlete_photos.derivatives') as $variant => $size) {
            $image = $this->coverCrop($source, $size['width'], $size['height']);
            ob_start();
            imagejpeg($image, null, 78);
            $contents = (string) ob_get_clean();
            Storage::disk($upload->disk)->put($this->variantPath($upload, $variant), $contents);
            imagedestroy($image);
        }
        imagedestroy($source);
    }
}

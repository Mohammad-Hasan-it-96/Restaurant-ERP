<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ImageService
 *
 * Compresses and downscales uploaded images on the way into storage, using the
 * GD extension (no external dependency). Large camera/phone uploads are the main
 * driver of page weight, so capping the longest edge and re-encoding at a sane
 * quality typically cuts the stored size by 60–90% with no visible difference at
 * the sizes the UI actually renders (≤ ~600px thumbnails).
 *
 * Returns the same "dir/filename.ext" relative path that UploadedFile::store()
 * would, so callers and asset('storage/'.$path) URLs are unaffected.
 */
class ImageService
{
    /**
     * Store an uploaded image, compressed and resized.
     *
     * @param  string  $dir  Sub-directory on the public disk (e.g. "products")
     * @param  int  $maxWidth  Longest-edge cap in px; wider images are scaled down
     * @param  int  $quality  JPEG/WebP quality (1–100)
     * @return string Relative path on the public disk, e.g. "products/ab12….jpg"
     */
    public function store(UploadedFile $file, string $dir, int $maxWidth = 1200, int $quality = 75): string
    {
        // No GD, or we can't introspect the file → fall back to a plain store so
        // an upload never fails just because compression isn't possible.
        if (! function_exists('imagecreatetruecolor')) {
            return $file->store($dir, 'public');
        }

        $path = $file->getRealPath();
        $info = @getimagesize($path);

        if ($info === false) {
            return $file->store($dir, 'public');
        }

        $type = $info[2];

        // GIFs may be animated; GD would flatten them. Anything we don't handle
        // is stored untouched rather than risking a broken/cropped result.
        $ext = match ($type) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => null,
        };

        if ($ext === null) {
            return $file->store($dir, 'public');
        }

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
        };

        if (! $src) {
            return $file->store($dir, 'public');
        }

        $width = imagesx($src);
        $height = imagesy($src);
        $hasAlpha = in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true);

        // Downscale only — never upscale a small image.
        $scale = $width > $maxWidth ? $maxWidth / $width : 1.0;

        if ($scale < 1.0) {
            $newW = (int) round($width * $scale);
            $newH = (int) round($height * $scale);
            $dst = imagecreatetruecolor($newW, $newH);

            if ($hasAlpha) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($src);
        } else {
            $dst = $src;
        }

        // Encode to an in-memory string so we can compare against the original.
        ob_start();
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($dst, null, $quality),
            IMAGETYPE_PNG => imagepng($dst, null, 6),   // 0–9 zlib level
            IMAGETYPE_WEBP => imagewebp($dst, null, $quality),
        };
        $binary = ob_get_clean();
        imagedestroy($dst);

        // If we neither resized nor shrank the bytes, keep the original as-is.
        if ($scale === 1.0 && $binary !== false && strlen($binary) >= (int) @filesize($path)) {
            return $file->store($dir, 'public');
        }

        if ($binary === false) {
            return $file->store($dir, 'public');
        }

        $filename = pathinfo($file->hashName(), PATHINFO_FILENAME).'.'.$ext;
        $storePath = trim($dir, '/').'/'.$filename;

        Storage::disk('public')->put($storePath, $binary);

        Log::debug('image.compressed', [
            'dir' => $dir,
            'original_bytes' => (int) @filesize($path),
            'stored_bytes' => strlen($binary),
        ]);

        return $storePath;
    }
}

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Uploaded image compression (App\Services\ImageService)
    |--------------------------------------------------------------------------
    |
    | GD-based compression applied to uploads. Tune per deployment for the
    | size/quality trade-off.
    |
    */

    // Longest-edge cap in pixels; wider images are scaled down.
    'max_width' => (int) env('IMAGE_MAX_WIDTH', 1200),

    // JPEG/WebP quality (1–100).
    'quality' => (int) env('IMAGE_QUALITY', 75),

    // PNG zlib compression level (0–9).
    'png_compression_level' => (int) env('IMAGE_PNG_COMPRESSION', 6),

];

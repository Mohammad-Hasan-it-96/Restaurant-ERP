<?php

namespace App\Support;

/**
 * EnvWriter — the only thing that writes the .env file.
 *
 * Upserts KEY=value pairs while preserving the rest of the file. The path is
 * injectable so tests can point it at a temp file instead of the real .env.
 */
class EnvWriter
{
    public function __construct(private ?string $path = null) {}

    private function path(): string
    {
        return $this->path ?? base_path('.env');
    }

    /** Create .env from .env.example (or empty) when it doesn't exist yet. */
    public function ensureExists(): void
    {
        $path = $this->path();

        if (! is_file($path)) {
            if (is_file(base_path('.env.example'))) {
                copy(base_path('.env.example'), $path);
            } else {
                file_put_contents($path, '');
            }
        }
    }

    /**
     * Upsert each KEY=value pair, preserving all other lines.
     *
     * @param  array<string, mixed>  $values
     */
    public function write(array $values): void
    {
        $this->ensureExists();
        $path = $this->path();
        $contents = (string) file_get_contents($path);

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->format($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            if (preg_match($pattern, $contents)) {
                // Callback avoids $/\ being interpreted in the replacement string.
                $contents = preg_replace_callback($pattern, fn () => $line, $contents);
            } else {
                $contents = rtrim($contents, "\r\n").PHP_EOL.$line.PHP_EOL;
            }
        }

        file_put_contents($path, $contents);
    }

    private function format(mixed $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        // Quote values containing whitespace, '#', or '"'.
        if (preg_match('/\s|#|"/', $value)) {
            return '"'.addcslashes($value, '"\\').'"';
        }

        return $value;
    }
}

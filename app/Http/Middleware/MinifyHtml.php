<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class MinifyHtml
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (! $request->expectsJson() &&
            $response instanceof Response &&
            str_contains($response->headers->get('Content-Type'), 'text/html')) {
            $content = $response->getContent();

            // Save <script> and <style> blocks
            $scripts = [];
            $styles = [];

            $content = preg_replace_callback('/<script\b[^>]*>.*?<\/script>/is', function ($matches) use (&$scripts) {
                $placeholder = '<!--SCRIPT'.count($scripts).'-->';
                $scripts[] = $matches[0];

                return $placeholder;
            }, $content);

            $content = preg_replace_callback('/<style\b[^>]*>.*?<\/style>/is', function ($matches) use (&$styles) {
                $placeholder = '<!--STYLE'.count($styles).'-->';
                $styles[] = $matches[0];

                return $placeholder;
            }, $content);

            // Minify HTML (careful patterns)
            $patterns = [
                '/\>[^\S ]+/s' => '>',  // strip spaces after tags, except space
                '/[^\S ]+\</s' => '<',  // strip spaces before tags, except space
                '/(\s)+/s' => ' ',  // shorten multiple whitespace sequences
                '/<!--(?!SCRIPT|STYLE).*?-->/' => '',  // Remove comments except placeholders
            ];

            $minified = preg_replace(array_keys($patterns), array_values($patterns), (string) $content);

            if ($minified === null) {
                logService()->error('minify_html.preg_replace_null');
                $minified = (string) $content;
            }

            // Restore <script> and <style> blocks
            foreach ($scripts as $i => $script) {
                $minified = str_replace("<!--SCRIPT$i-->", $script, $minified);
            }
            foreach ($styles as $i => $style) {
                $minified = str_replace("<!--STYLE$i-->", $style, $minified);
            }

            if (! empty($minified)) {
                $response->setContent($minified);
                // Optional: Laravel automatically recalculates Content-Length if needed
            }
        }

        return $response;
    }
}

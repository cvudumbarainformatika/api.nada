<?php
header("Access-Control-Allow-Origin: *");
/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;


    // $filePath = __DIR__ . '/public' . $uri;
    // header("Access-Control-Allow-Origin: *");
    // //This just works for images and videos. If you need to return css and js take at a look and the following links
    // $mime = mime_content_type($filePath);
    // // https://stackoverflow.com/questions/45179337/mime-content-type-returning-text-plain-for-css-and-js-files-only
    // // https://stackoverflow.com/questions/7236191/how-to-create-a-custom-magic-file-database
    // header("Content-type: {$mime}");
    // echo file_get_contents($filePath);
    // return true;
}

require_once __DIR__ . '/public/index.php';

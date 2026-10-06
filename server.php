<?php

// The router for `composer start`: PHP's built-in server with upload limits for 5 MB files.
// `php artisan serve` starts a child PHP process that doesn't inherit -d flags, so
// composer.json runs `php -d upload_max_filesize=10M -d post_max_size=12M -S … server.php`
// directly. Same logic as Laravel's own router, with public/ resolved from this file.

$publicPath = __DIR__.'/public';

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

// Serve built assets, favicon.ico and robots.txt as they are
if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';

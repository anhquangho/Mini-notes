<?php
// Development test server only: php -S 127.0.0.1:8091 -t WORDPRESS_ROOT tools/router.php
$root = getenv('MN_ROOT');
if (!$root) { http_response_code(500); exit('MN_ROOT missing'); }
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath($root . rawurldecode($path));
$inside = $file && str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', realpath($root)) . '/');
if ($inside && is_file($file)) { return false; }
if ($inside && is_dir($file) && file_exists($file . '/index.php')) {
    require $file . '/index.php';
    return true;
}
require $root . '/index.php';

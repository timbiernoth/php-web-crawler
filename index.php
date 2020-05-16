<?php

////////////////////////////////////////////////////////////////////////////////

$requires = [
    'config.php',
    'helpers/default.php',
    'types/tlds.php',
    'helpers/domains_crawler.php',
    'classes/default/HtmlPage.class.php',
    'classes/DataBaseCrawler.class.php',
];

foreach ($requires as $require) {
    require_once 'php/' .$require;
}

////////////////////////////////////////////////////////////////////////////////

setlocale(LC_TIME, CONFIG_LOCALE);
set_time_limit(20);

////////////////////////////////////////////////////////////////////////////////

$html_page = new HtmlPage([
    'assets' => [
        'css' => [
            'assets/css/domain.css',
        ],
        'js' => [
            'assets/js/domain.js',
        ],
    ],
]);

////////////////////////////////////////////////////////////////////////////////

$db = new DataBaseCrawler(CONFIG_DATABASE_CONNECTION, CONFIG_DATABASE_TABLES);

////////////////////////////////////////////////////////////////////////////////

define('DOMAIN', 'https://www.example1.local');
define('PATH', '');
define('RURI', $_SERVER['REQUEST_URI']);

$main = '';
$header = '';
$footer = '';

////////////////////////////////////////////////////////////////////////////////

$pages = [

    '/' => 'pages/home.php',
    '404' => 'pages/404.php',

];

////////////////////////////////////////////////////////////////////////////////

$page_include = '';
$e404 = true;
foreach ($pages as $page => $to_include) {
    if (strtolower(RURI) == PATH.$page) {
        $page_include = $to_include;
        $e404 = false;
    }
}

if ($e404 !== false) {
    header('HTTP/1.0 404 Not Found');
    $page_include = $pages['404'];
}

////////////////////////////////////////////////////////////////////////////////

require_once $page_include;
require_once 'partials/header.php';
require_once 'partials/footer.php';

////////////////////////////////////////////////////////////////////////////////

header('Content-Type:text/html;charset=utf-8');
$html_page->render([
    'head' => [
        'title' => 'Bot | WordRank',
    ],
    'body' => [
        'main' => $main,
        'header' => $header,
        'footer' => $footer,
    ],
]);

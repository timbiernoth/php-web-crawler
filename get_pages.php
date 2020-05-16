<?php

////////////////////////////////////////////////////////////////////////////////

$requires = [
    'config.php',
    'types/tlds.php',
    'types/specials.php',
    'types/fileformats.php',
    'helpers/default.php',
    'helpers/pages_crawler.php',
    'helpers/domains_crawler.php',
    'classes/RobotsTxt.class.php',
    'classes/DataBaseCrawler.class.php',
    'classes/PagesUrl.class.php',
    'classes/PagesCurl.class.php',
    'classes/PagesCrawler.class.php',
];

foreach ($requires as $require) {
    require_once 'php/' .$require;
}

////////////////////////////////////////////////////////////////////////////////

setlocale(LC_TIME, CONFIG_LOCALE);
set_time_limit(CONFIG_TIME_LIMIT);

////////////////////////////////////////////////////////////////////////////////

$url = '';

$pages_url = new PagesUrl($url);

if ($pages_url->error !== false) {
    prexit($pages_url->errorMassage);
}

////////////////////////////////////////////////////////////////////////////////

$pages_crawler = new PagesCrawler(
    CONFIG_PAGES_CRAWLER,
    new PagesCurl(CONFIG_PAGES_CURL),
    $pages_url->url,
    $pages_url->ignore,
    new DataBaseCrawler(CONFIG_DATABASE_CONNECTION, CONFIG_DATABASE_TABLES)
);

////////////////////////////////////////////////////////////////////////////////

json($pages_crawler->api);

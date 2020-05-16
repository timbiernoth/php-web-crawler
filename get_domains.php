<?php

////////////////////////////////////////////////////////////////////////////////

$requires = [
    'config.php',
    'types/tlds.php',
    'helpers/default.php',
    'helpers/domains_crawler.php',
    'classes/default/LoadTime.class.php',
    'classes/DataBaseCrawler.class.php',
    'classes/DomainsUrl.class.php',
    'classes/DomainsCurl.class.php',
    'classes/DomainsCrawler.class.php',
];

foreach ($requires as $require) {
    require_once 'php/' .$require;
}

////////////////////////////////////////////////////////////////////////////////

setlocale(LC_TIME, CONFIG_LOCALE);
set_time_limit(CONFIG_TIME_LIMIT);

////////////////////////////////////////////////////////////////////////////////

$db = new DataBaseCrawler(
    CONFIG_DATABASE_CONNECTION,
    CONFIG_DATABASE_TABLES
);

$domains_crawler = new DomainsCrawler(
    CONFIG_DOMAINS_CRAWLER,
    new DomainsCurl,
    new DomainsUrl($db),
    new LoadTime,
    $db
);

////////////////////////////////////////////////////////////////////////////////

json($domains_crawler->api);

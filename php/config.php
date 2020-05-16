<?php

////////////////////////////////////////////////////////////////////////////////

define('DEBUG', true);

define('LOCAL', true);
define('LOCAL_DB', true);

define('DOMAINS_CRAWLER_MAX_DOMAINS', 5); // 10
define('DOMAINS_CRAWLER_MAX_LOOPS', 1); // 1
define('PAGES_CRAWLER_MAX_THREADS', 5); // 5
define('PAGES_CRAWLER_MAX_LOOPS', 5); // 5
define('PAGES_CRAWLER_CONNECT_TIMEOUT', 3); // 3
define('PAGES_CRAWLER_TIMEOUT', 3); // 3

////////////////////////////////////////////////////////////////////////////////

define('CONFIG_LOCALE', 'de_DE.utf-8');
define('CONFIG_TIME_LIMIT', 600);

////////////////////////////////////////////////////////////////////////////////

if (DEBUG !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

////////////////////////////////////////////////////////////////////////////////

$bot_get_pages_url = 'https://bot.example.com/get_pages.php?url=';
if (LOCAL !== false) {
    $bot_get_pages_url = 'https://bot.example.local/get_pages.php?url=';
}

define('CONFIG_BOT_GET_PAGES_URL', $bot_get_pages_url);
unset($bot_get_pages_url);

////////////////////////////////////////////////////////////////////////////////

$bot_get_start_urls = [
    'https://www.google.com/',
];
if (LOCAL !== false) {
    $bot_get_start_urls = [
        'https://www.example.local/',
    ];
}

define('CONFIG_BOT_GET_START_URLS', $bot_get_start_urls);
unset($bot_get_pages_urls);

////////////////////////////////////////////////////////////////////////////////

$domains_crawler = [
    'max_domains' => DOMAINS_CRAWLER_MAX_DOMAINS,
    'max_loops' => DOMAINS_CRAWLER_MAX_LOOPS,
];

define('CONFIG_DOMAINS_CRAWLER', $domains_crawler);
unset($domains_crawler);

////////////////////////////////////////////////////////////////////////////////

$pages_crawler = [
    'max_loops' => PAGES_CRAWLER_MAX_LOOPS,
    'do_loops' => true,
    'datetime_format' => 'Y-m-d H:i:s',
    'robots_txt_user_agent' => [
        'name' => 'Googlebot',
        'mobile' => 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.96 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'desktop' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        #'name' => 'WordRankBot',
        #'mobile' => 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.96 Mobile Safari/537.36 (compatible; WordRankBot/1.0; +http://www.wordank.org/about/bot/)',
        #'desktop' => 'Mozilla/5.0 (compatible; WordRankBot/1.0; +http://www.wordank.org/about/bot/)',
    ],
];

define('CONFIG_PAGES_CRAWLER', $pages_crawler);
unset($pages_crawler);

////////////////////////////////////////////////////////////////////////////////

$ssl_verify_host = 2;
$ssl_verify_peer = true;
if (LOCAL !== false) {
    $ssl_verify_host = 0;
    $ssl_verify_peer = false;
}

$domains_curl = [
    'userpwd' => 'wr:TestThis2018%',
    'ssl_verify_host' => $ssl_verify_host,
    'ssl_verify_peer' => $ssl_verify_peer,
    'header' => false,
    'return_transfer' => true,
];

define('CONFIG_DOMAINS_CURL', $domains_curl);
unset($domains_curl);
unset($ssl_verify_host);
unset($ssl_verify_peer);

////////////////////////////////////////////////////////////////////////////////

$ssl_verify_host = 2;
$ssl_verify_peer = true;
if (LOCAL !== false) {
    $ssl_verify_host = 0;
    $ssl_verify_peer = false;
}

$pages_curl = [
    'max_threads' => PAGES_CRAWLER_MAX_THREADS,
    'user_agent' => 'mobile',
    'connect_timeout' => PAGES_CRAWLER_CONNECT_TIMEOUT,
    'timeout' => PAGES_CRAWLER_TIMEOUT,
    'max_redirects' => 5,
    'ssl_verify_host' => $ssl_verify_host,
    'ssl_verify_peer' => $ssl_verify_peer,
    'header' => true,
    'nobody' => false,
    'return_transfer' => true,
    'follow_location' => false,
    'auto_referer' => true,
    'fresh_connect' => true,
    'encoding' => '',
    'datetime_format' => 'Y-m-d H:i:s',
];

define('CONFIG_PAGES_CURL', $pages_curl);
unset($pages_curl);
unset($ssl_verify_host);
unset($ssl_verify_peer);

////////////////////////////////////////////////////////////////////////////////

$db_live = [
    'host' => '',
    'username' => '',
    'password' => '',
    'database' => '',
];

$db_local = [
    'host' => '127.0.0.1',
    'username' => 'root',
    'password' => '',
    'database' => '000_bot',
];

$db_connection = [];
if (LOCAL !== true) {
    $db_connection = $db_live;
} else {
    if (LOCAL_DB !== true) {
        $db_connection = $db_live;
    } else {
        $db_connection = $db_local;
    }
}

define('CONFIG_DATABASE_CONNECTION', $db_connection);
unset($db_connection);
unset($db_live);
unset($db_local);

////////////////////////////////////////////////////////////////////////////////

$db_tables = [

    'log' => '000_000_log',
    'langs' => '000_001_langs',

    'robots_txt' => '000_010_robots_txt',
    'ips' => '000_020_ips',
    'prots' => '000_030_prots',
    'names' => '000_040_names',
    'tlds' => '000_050_tlds',
    'ruris' => '000_060_ruris',
    'non_http' => '000_070_non_http',

    'domains' => '001_010_domains',
    'domains_count' => '001_011_domains_count',
    'domains_crawler' => '001_020_domains_crawler',
    'domains_blacklist' => '001_021_domains_blacklist',
    'domains_types_ignored' => '001_022_domains_types_ignored',
    'domains_counts_links' => '001_030_domains_counts_links',

    'pages' => '002_010_pages',
    'pages_crawler' => '002_020_pages_crawler',
    'pages_non_http' => '002_021_pages_non_http',
    'pages_non_indexable' => '002_022_pages_non_indexable',
    'pages_hreflang' => '002_031_pages_hreflang',
    'pages_non_canonical' => '002_032_pages_non_canonical',
    'pages_a_tags' => '002_040_pages_a_tags',

];

define('CONFIG_DATABASE_TABLES', $db_tables);
unset($db_tables);

////////////////////////////////////////////////////////////////////////////////

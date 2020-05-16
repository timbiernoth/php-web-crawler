<?php

////////////////////////////////////////////////////////////////////////////////

$requires = [
    'config.php',
    'helpers/default.php',
    'types/tlds.php',
    'types/languages.php',
    'classes/DataBaseCrawler.class.php',
];

foreach ($requires as $require) {
    require_once 'php/' .$require;
}

////////////////////////////////////////////////////////////////////////////////

setlocale(LC_TIME, CONFIG_LOCALE);
set_time_limit(300);

////////////////////////////////////////////////////////////////////////////////

$db = new DataBaseCrawler(CONFIG_DATABASE_CONNECTION, CONFIG_DATABASE_TABLES);

////////////////////////////////////////////////////////////////////////////////

$langs = array_merge([
    '' => 'n/a',
    '.' => 'n/a',
], LANGUAGES);

foreach ($langs as $code => $lang) {
    $db->insertDefaults('000_001_langs', ['code' => $code, 'lang' => $lang]);
}

////////////////////////////////////////////////////////////////////////////////

$robots_txts = [
    '',
    '.',
    'User-agent: *',
    'User-agent: *

Disallow: /',
    'User-agent: *
Disallow: /',
    'User-agent: *

Allow: /',
    'User-agent: *
Allow: /',
];

foreach ($robots_txts as $robots_txt) {
    $db->insertDefaults('000_010_robots_txt', ['code' => $robots_txt]);
}

////////////////////////////////////////////////////////////////////////////////

$ips = [
    '',
    '.',
    '127.0.0.1',
    '192.168.0.1',
];

foreach ($ips as $ip) {
    $db->insertDefaults('000_020_ips', ['ip' => $ip]);
}

////////////////////////////////////////////////////////////////////////////////

$prots = [
    '',
    '.',
    'https',
    'http',
];

foreach ($prots as $prot) {
    $db->insertDefaults('000_030_prots', ['slug' => $prot]);
}

////////////////////////////////////////////////////////////////////////////////

$names = [
    '',
    '.',
    '127.0.0.1:8080',
    '127.0.0.1',
    '192.168.0.1',
    'localhost',
];

foreach ($names as $name) {
    $db->insertDefaults('000_040_names', ['slug' => $name, 'is_www' => 0]);
}

////////////////////////////////////////////////////////////////////////////////

$tlds = array_merge(
    [
        '' => [
            'type' => 'special',
            'info' => 'local'
        ],
        '.' => [
            'type' => 'special',
            'info' => 'local'
        ],
    ],
    TLDS,
    TLDS_CUSTOM
);

foreach ($tlds as $tld => $data) {
    $db->insertDefaults('000_050_tlds', [
        'slug' => $tld,
        'type' => $data['type'],
        'info' => $data['info'],
    ]);
}

////////////////////////////////////////////////////////////////////////////////

$ruris = [
    '',
    '.',
    '/',
    '/index.html',
    '/index.htm',
    '/index.php',
    '/index.phps',
    '/index.asp',
    '/index.aspx',
];

foreach ($ruris as $ruri) {
    $db->insertDefaults('000_060_ruris', ['slug' => $ruri]);
}

////////////////////////////////////////////////////////////////////////////////

$non_https = [
    '',
    '.',
    'javascript:',
    'javascript:;',
    'javascript:void(0)',
    'javascript:void(0;',
    'mailto:',
    'tel:',
    'fon:',
    'callto:',
];

foreach ($non_https as $non_http) {
    $db->insertDefaults('000_070_non_http', ['url' => $non_http]);
}

////////////////////////////////////////////////////////////////////////////////

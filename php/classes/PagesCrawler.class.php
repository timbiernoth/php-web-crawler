<?php

class PagesCrawler
{
    public $api = [
        'domain' => [
            'url' => '',
            'name' => '',
            'protocol' => '',
            'db' => [],
            'info' => [],
            'specials' => [],
            'time' => [
                'ttfb_avg' => 0,
                'loadtime' => 0,
                'datetime_start' => '',
                'datetime_end' => '',
            ],
            'count' => [],
            'pages' => [],
        ],
        'non_indexable' => [
            'blocked' => [],
            'non_200' => [],
            'non_html' => [],
            'nofollow' => [],
            'redirect' => [],
        ],
        'notices' => [
            'non_http' => [],
            'non_canonical' => [],
        ],
        'pages' => [],
    ];

    private $db = [];

    private $timings = [
        'ttfb' => 0,
        'load' => 0,
        'load_start' => 0,
        'load_stop' => 0,
    ];

    private $urls = [
        'loops' => 0,
        'done' => [],
        'todo' => [],
        'intern' => [],
        'extern' => [],
        'intern_follow' => [],
        'extern_follow' => [],
        'contents' => [],
    ];

    private $ignore = [
        'parameters' => [],
        'pathes' => [],
        'urls' => [],
    ];

    private $config = [
        'max_loops' => 10,
        'do_loops' => true,
        'datetime_format' => 'Y-m-d H:i:s',
        'robots_txt_user_agent' => [
            'mobile' => '',
            'desktop' => '',
        ],
    ];

    private $curl = [];
    private $robotsTxt = [];
    private $robotsTxtUserAgent = '';
    private $robotsTxtUserAgentName = '';

    public function __construct($config = [], $curl = [], $url = '', $ignore = [], $db = [])
    {
        $this->setConfig($config);
        $this->setIgnore($ignore);
        $this->setCurl($curl);
        $this->setDb($db);
        $this->setGo($url);
    }

    private function setGo($url)
    {
        if ($url !== '') {

            $this->setLoadTimeStart();

            $this->setDomain($url);
            $this->setRobotsTxt();

            $this->checkAndSort($url);

            if ($this->config['do_loops'] !== false) {
                $this->crawl();
            }

        } else {
            prexit('No URL!');
        }
    }

    private function checkAndSort($url)
    {
        if (trim($url) !== '' &&
            $this->urls['loops'] <= $this->config['max_loops']) {

            $this->urls['loops'] += 1;

            if ($this->isNotDone($url) &&
                $this->isNotIgnored($url)) {

                array_push($this->urls['done'], $url);

                if ($this->isNotBlocked($url) &&
                    $this->db->isNotBlacklistedDomain($url)) {

                    $content = '';
                    if ($this->urls['loops'] == 1 || count($this->urls['todo']) == 1) {
                        $content = $this->getContentSingle($url);
                    } else {
                        $content = $this->getContent($url);
                    }

                    if (is_final_url($url, $content['final_url'])) {

                        if (is_200($content['status'])) {

                            if (is_html($content['content_type'])) {

                                $document = get_document(get_code($content['code']));

                                if (is_not_nofollow_page($document)) {

                                    $this->api['pages'][$url]['data']['db']['domains_id'] =
                                        $this->api['domain']['db']['domains_id'];

                                    $ruris_id = $this->db->dataByValues([
                                        'table' => 'ruris',
                                        'datas' => [
                                            'slug' => get_ruri($url),
                                        ],
                                    ], 'update');

                                    $this->api['pages'][$url]['data']['db']['ruris_id'] =
                                        $ruris_id;

                                    $ips_id = $this->db->dataByValues([
                                        'table' => 'ips',
                                        'datas' => [
                                            'ip' => $content['ip'],
                                        ],
                                    ], 'update');

                                    $this->api['pages'][$url]['data']['db']['ips_id'] =
                                        $ips_id;
                                    $this->api['pages'][$url]['data']['ip'] = $content['ip'];

                                    $pages_crawler_id = $this->db->dataByIds([
                                        'table' => 'pages_crawler',
                                        'ids' => [
                                            'domains_crawler_id' => $this->api['domain']['db']['domains_crawler_id'],
                                            'ruris_id' => $ruris_id,
                                            'ips_id' => $ips_id,
                                        ],
                                    ], 'update');

                                    $this->api['pages'][$url]['data']['db']['pages_crawler_id'] =
                                        $pages_crawler_id;

                                    $this->api['pages'][$url]['data']['db']['domains_crawler_id'] =
                                        $this->api['domain']['db']['domains_crawler_id'];

                                    $html = substr($content['code'], 0, 2000000);

                                    $this->db->dataByIds([
                                        'table' => 'pages',
                                        'ids' => [
                                            'pages_crawler_id' => $pages_crawler_id,
                                        ],
                                        'datas' => [
                                            'last_crawled' => get_datetime(),
                                            'type' => strtolower($content['content_type']),
                                            'code' => $html,
                                        ],
                                    ], 'update');

                                    $this->setATagsData($url, $document, $content);
                                    $this->setUrlsTodo($url);

                                    $this->setIndexableApiData($url);

                                } else {

                                    $this->setNonIndexable($url, 'nofollow', 'nofollow', $content);

                                }

                            } else {

                                $this->setNonIndexable($url, 'non_html', $content['content_type'], $content);

                            }

                        } else {

                            $this->setNonIndexable($url, 'non_200', $content['status'], $content);

                        }

                    } else {

                        $this->setNonIndexable($url, 'redirect', $content['final_url'], $content);

                    }

                } else {

                    $this->setNonIndexable($url, 'blocked', 'blocked');

                }

            }

            $this->setApiData($url);

        }
    }

    private function crawl()
    {
        $this->urls['contents'] = [];
        $todo = array_slice($this->urls['todo'], 0, $this->curl->config['max_threads']);

        $urls = [];
        foreach ($todo as $url => $is) {
            array_push($urls, $url);
            unset($this->urls['todo'][$url]);
        }

        if ( ! empty($urls) ) {
            $this->urls['contents'] = $this->getContents($urls);
            if ($this->curl->status == 'done') {
                foreach ($urls as $url) {
                    $this->checkAndSort($url);
                }
                $this->crawl();
            }

        } else {
            $this->urls['contents'] = [];
            $this->setLoadTimeStop();
            $this->api['domain']['time']['loadtime'] = round($this->timings['load'], 2);
            $this->api['domain']['time']['datetime_end'] = get_datetime($this->config['datetime_format']);

            $domains_crawler_id = $this->api['domain']['db']['domains_crawler_id'];
            $this->db->dataByIds([
                'table' => 'domains_crawler',
                'ids' => [
                    'id' => $domains_crawler_id,
                ],
                'datas' => [
                    'status' => 'done',
                    'crawl_end' => get_datetime(),
                ],
            ], 'update');

            $this->db->dataByIds([
                'table' => 'domains_count',
                'ids' => [
                    'domains_id' => $this->api['domain']['db']['domains_id'],
                ],
                'datas' => [
                    'crawl_loadtime' => round($this->timings['load'], 2),
                ],
            ], 'update');
        }
    }

    private function getContent($url)
    {
        $content = $this->urls['contents'][$url];

        return [
            'ip' => $content['ip'],
            'code' => $content['body'],
            'status' => $content['status'],
            'final_url' => $content['final_url'],
            'content_type' => $content['content_type'],
            'ttfb' => round($content['ttfb'], 2),
            'datetime' => $content['datetime'],
            'content_size' => $content['content_size'],
            'headers' => $content['headers'],
        ];
    }

    private function getContents($urls)
    {
        $contents = $this->curl->getContents($urls);

        return $contents;
    }

    private function getContentSingle($url)
    {
        $content = $this->curl->getContents([$url]);

        return [
            'ip' => $content[$url]['ip'],
            'code' => $content[$url]['body'],
            'status' => $content[$url]['status'],
            'final_url' => $content[$url]['final_url'],
            'content_type' => $content[$url]['content_type'],
            'ttfb' => round($content[$url]['ttfb'], 2),
            'datetime' => $content[$url]['datetime'],
            'content_size' => $content[$url]['content_size'],
            'headers' => $content[$url]['headers'],
        ];
    }

    private function setRedirectTodo($url, $final_url)
    {
        $absolute_url = get_absolute_url_by_last_url($final_url, $url);

        if ( $this->isExtern($absolute_url) == false &&
             ! isset($this->urls['todo'][$absolute_url]) &&
             ! in_array($absolute_url, $this->urls['done'])) {
            $this->urls['todo'][$absolute_url] = true;
            $this->crawl();
        }
    }

    private function setNonIndexable($url, $name, $value, $content = [])
    {
        $domains_crawler_id = $this->api['domain']['db']['domains_crawler_id'];

        $status = 0;
        $final_url = '';
        if (isset($content['status'])) {
            $status = $content['status'];
            if (substr($status, 0, 1) == 3) {
                $name = 'redirect';
                $final_url = $content['final_url'];
                if ($final_url == $url) {
                    if (isset($content['headers'])) {
                        foreach ($content['headers'] as $header) {
                            if (substr_count(strtolower($header), 'location:') == 1) {
                                $header = str_replace(' ', '', $header);
                                $temp = explode(':', $header);
                                $url_build = '';
                                foreach ($temp as $frag) {
                                    if (strtolower($frag) !== 'location') {
                                        $url_build .= $frag . ':';
                                    }
                                }
                                $final_url = substr($url_build, 0, -1);
                            }
                        }
                    }
                }
                $this->setRedirectTodo($url, $final_url);
            }
        }

        if ( ! isset($this->api['non_indexable'][$name][$url])) {

            $this->api['non_indexable'][$name][$url] = $value;

            $this->db->dataByValues([
                'table' => 'pages_non_indexable',
                'datas' => [
                    'domains_crawler_id' => $domains_crawler_id,
                    'last_crawled' => get_datetime(),
                    'url' => $url,
                    'status' => $status,
                    'type' => $name,
                    'final_url' => $final_url,
                ],
            ], 'update');
        }
    }

    private function setApiData($url)
    {
        $count_all = count($this->urls['done']);

        $this->api['domain']['count'] = [
            'all_pages' => $count_all,
            'max_loops' => $this->config['max_loops'],
            'max_threads' => $this->curl->config['max_threads'],
            'non_indexable' => [
                'blocked' => count($this->api['non_indexable']['blocked']),
                'non_200' => count($this->api['non_indexable']['non_200']),
                'non_html' => count($this->api['non_indexable']['non_html']),
                'nofollow' => count($this->api['non_indexable']['nofollow']),
                'redirect' => count($this->api['non_indexable']['redirect']),
            ],
            'notices' => [
                'non_canonical' => count($this->api['notices']['non_canonical']),
                'non_http' => count($this->api['notices']['non_http']),
            ],
        ];

        $this->db->dataByIds([
            'table' => 'domains_count',
            'ids' => [
                'domains_id' => $this->api['domain']['db']['domains_id'],
            ],
            'datas' => [
                'pages_blocked' => count($this->api['non_indexable']['blocked']),
                'pages_non_200' => count($this->api['non_indexable']['non_200']),
                'pages_non_html' => count($this->api['non_indexable']['non_html']),
                'pages_nofollow' => count($this->api['non_indexable']['nofollow']),
                'pages_redirect' => count($this->api['non_indexable']['redirect']),
                'pages_non_canonical' => count($this->api['notices']['non_canonical']),
                'links_non_http' => count($this->api['notices']['non_http']),
            ],
        ], 'update');
    }

    private function setIndexableApiData($url)
    {
        $count_pages = count($this->api['pages']);
        $count_done = count($this->urls['done']);

        $this->api['domain']['time']['ttfb_avg'] = round($this->timings['ttfb'] / $count_pages, 2);

        $intern_pages = $this->urls['intern'];
        $count_intern = count($intern_pages);
        $count_intern_unique = count(array_unique($intern_pages));

        $intern_follow_pages = $this->urls['intern_follow'];
        $count_intern_follow = count($intern_follow_pages);
        $count_intern_follow_unique = count(array_unique($intern_follow_pages));

        $extern_pages = $this->urls['extern'];
        $count_extern = count($extern_pages);
        $count_extern_unique = count(array_unique($extern_pages));

        $extern_follow_pages = $this->urls['extern_follow'];
        $count_extern_follow = count($extern_follow_pages);
        $count_extern_follow_unique = count(array_unique($extern_follow_pages));

        $this->api['domain']['pages'] = [
            'indexable' => $count_pages,
            'links' => [
                'follow' => [
                    'intern' => [
                        'all' => $count_intern_follow,
                        'unique' => $count_intern_follow_unique + 1,
                    ],
                    'extern' => [
                        'all' => $count_extern_follow,
                        'unique' => $count_extern_follow_unique,
                    ],
                ],
                'nofollow' => [
                    'intern' => [
                        'all' => ($count_intern - $count_intern_follow),
                        'unique' => ($count_intern_unique - $count_intern_follow_unique),
                    ],
                    'extern' => [
                        'all' => ($count_extern - $count_extern_follow),
                        'unique' => ($count_extern_unique - $count_extern_follow_unique),
                    ],
                ],
            ],
        ];

        $pages_crawler_id = $this->api['pages'][$url]['data']['db']['pages_crawler_id'];
        $this->db->dataByIds([
            'table' => 'pages_crawler',
            'ids' => [
                'id' => $pages_crawler_id,
            ],
            'datas' => [
                'pages_url' => $url,
                'last_crawled' => get_datetime(),
                'status' => $this->api['pages'][$url]['data']['status'],
                'ttfb' => $this->api['pages'][$url]['data']['ttfb'],
                'size' => $this->api['pages'][$url]['data']['size'],
                'is_gzip' => get_gzip($this->api['pages'][$url]['data']['headers']),
                'type' => str_replace(' ', '', trim(strtolower($this->api['pages'][$url]['data']['type']))),
                'base' => $this->api['pages'][$url]['data']['base'],
                'canonical' => $this->api['pages'][$url]['data']['canonical'],
                'final_url' => $this->api['pages'][$url]['data']['final_url'],
                'header' => json_encode($this->api['pages'][$url]['data']['headers']),
                'a_tags_pages_count' => $this->api['pages'][$url]['data']['a_tags_pages_count'],
            ],
        ], 'update');

        $domains_crawler_id = $this->api['domain']['db']['domains_crawler_id'];
        $this->db->dataByIds([
            'table' => 'domains_crawler',
            'ids' => [
                'id' => $domains_crawler_id,
            ],
            'datas' => [
                'status' => 'running',
                'crawl_end' => get_datetime(),
                'specials' => json_encode($this->api['domain']['specials']),
            ],
        ], 'update');

        $this->db->dataByIds([
            'table' => 'domains_count',
            'ids' => [
                'domains_id' => $this->api['domain']['db']['domains_id'],
            ],
            'datas' => [
                'datetime' => get_datetime(),
                'max_loops' => $this->config['max_loops'],
                'max_threads' => $this->curl->config['max_threads'],
                'pages_ttfb_avg' => $this->api['domain']['time']['ttfb_avg'],
                'crawled_pages' => count($this->urls['done']),
                'indexable_pages' => $count_pages,
                'links_to_intern_follow_all' => $count_intern_follow,
                'links_to_intern_follow_unique' => $count_intern_follow_unique + 1,
                'links_to_extern_follow_all' => $count_extern_follow,
                'links_to_extern_follow_unique' => $count_extern_follow_unique,
                'links_to_intern_nofollow_all' => ($count_intern - $count_intern_follow),
                'links_to_intern_nofollow_unique' => ($count_intern_unique - $count_intern_follow_unique),
                'links_to_extern_nofollow_all' => ($count_extern - $count_extern_follow),
                'links_to_extern_nofollow_unique' => ($count_extern_unique - $count_extern_follow_unique),
            ],
        ], 'update');
    }

    private function setUrlsTodoPriority($a_tags, $url)
    {
        $a_tags_origin = $a_tags;

        $a_tags = [];
        $a_tags_top = [];
        $a_tags_last = [];

        foreach ($a_tags_origin as $href => $data) {

            if ($this->isExtern($href) !== true &&
                $href !== $this->api['domain']['url']) {

                $temp = @explode('/', $href);
                $page = strtolower(end($temp));
                $anchor = strtolower(arr_to_str($data['anchor']));

                foreach (SPECIALS['top'] as $name => $info_data) {
                    if (substr_count($page, $name) >= 1 ||
                        substr_count($anchor, $name) >= 1) {
                        $a_tags_top[$href] = $data;
                        $info = $info_data['info'];
                        if ( ! isset($this->api['domain']['specials'][$info])) {
                            $this->api['domain']['specials'][$info] = $href;
                        }
                    }
                }

                if ( ! isset($a_tags_top[$href])) {
                    foreach (SPECIALS['last'] as $name => $info_data) {
                        if (substr_count($page, $name) >= 1 ||
                            substr_count($anchor, $name) >= 1) {
                            $a_tags_last[$href] = $data;
                            $info = $info_data['info'];
                            if ( ! isset($this->api['domain']['specials'][$info])) {
                                $this->api['domain']['specials'][$info] = $href;
                            }
                        }
                    }
                }

                if ( ! isset($a_tags_top[$href]) &&
                     ! isset($a_tags_last[$href])) {
                    $a_tags[$href] = $data;
                }

            }
        }

        return array_merge($a_tags_top, $a_tags, $a_tags_last);
    }

    private function setUrlsTodo($url)
    {
        unset($this->urls['todo'][$url]);

        $a_tags = $this->api['pages'][$url]['a_tags'];
        $a_tags = $this->setUrlsTodoPriority($a_tags, $url);

        foreach ($a_tags as $href => $data) {

            if ( ! isset($this->urls['todo'][$href]) &&
                $data['is_page'] !== false &&
                $data['is_intern'] !== false &&
                $data['is_follow'] !== false &&
                $data['is_not_done'] !== false &&
                $data['is_not_ignored'] !== false &&
                $data['is_not_blocked'] !== false) {

                $this->urls['todo'][$href] = true;

            }
        }
    }

    private function setATagsData($url, $document, $content)
    {
        $this->api['pages'][$url]['data']['datetime'] = $content['datetime'];
        $this->api['pages'][$url]['data']['status'] = $content['status'];

        $this->timings['ttfb'] += $content['ttfb'];
        $this->api['pages'][$url]['data']['ttfb'] = $content['ttfb'];

        $this->api['pages'][$url]['data']['size'] = $content['content_size'];

        $this->api['pages'][$url]['data']['a_tags_pages_count'] = 0;

        $this->api['pages'][$url]['data']['type'] = $content['content_type'];
        $this->api['pages'][$url]['data']['base'] = $this->getBaseTag($document);
        $this->api['pages'][$url]['data']['canonical'] = $this->getCanonicalTag($document, $url);
        $this->api['pages'][$url]['data']['final_url'] = $content['final_url'];

        $hreflang = get_hreflang($document);
        $this->api['pages'][$url]['data']['hreflang'] = $hreflang;

        $pages_crawler_id = $this->api['pages'][$url]['data']['db']['pages_crawler_id'];
        if ( ! empty($hreflang)) {
            foreach ($hreflang as $hreflang_code => $hreflang_url) {

                $code = get_hreflang_code($this->db->defaults, $hreflang_code);

                $this->db->dataByIds([
                    'table' => 'pages_hreflang',
                    'ids' => [
                        'pages_crawler_id' => $pages_crawler_id,
                        'langs_id' => $code['langs_id'],
                        'tlds_id' => $code['tlds_id'],
                    ],
                    'datas' => [
                        'last_crawled' => get_datetime(),
                        'code' => $code['code'],
                        'url' => $hreflang_url,
                    ],
                ], 'update');

            }
        }

        $this->api['pages'][$url]['data']['headers'] = $content['headers'];

        $document_body = get_document_body(get_code($content['code']));

        $a_tags = $this->getATags($document_body, $url);

        $a_tags_data = $this->getATagsData($a_tags, $url);
        $a_tags_data_count = $this->getATagsCount($a_tags_data);

        $this->api['pages'][$url]['data']['a_tags_pages_count'] = $a_tags_data_count;
        $this->api['pages'][$url]['a_tags'] = $a_tags_data;
    }

    private function getATags($doc, $url)
    {
        $a_tags = [];

        foreach ($doc->getElementsByTagName('a') as $a) {

            $href_origin = trim($a->getAttribute('href'));
            $inner_img = get_inner_img($a);

            if ($this->isHttp($href_origin)) {

                $href = $this->getUrlClean($href_origin, $url);

                if ( ! empty($href) &&
                    $href !== '' &&
                    $this->isNotSelf($href, $url) !== false) {

                    array_push($a_tags, [
                        'href' => $href,
                        'follow' => get_href_follow($a),
                        'anchor' => trim($a->textContent),
                        'title' => trim($a->getAttribute('title')),
                        'lang' => trim($a->getAttribute('lang')),
                        'hreflang' => trim($a->getAttribute('hreflang')),
                        'anchor_img' => $inner_img,
                    ]);

                }

            } else {
                if ( ! in_array($href_origin, $this->api['notices']['non_http']) ) {

                    array_push($this->api['notices']['non_http'], $href_origin);

                    $non_http_id = $this->db->dataByValues([
                        'table' => 'non_http',
                        'datas' => [
                            'url' => $href_origin,
                        ],
                    ], 'update');

                    $this->db->dataByIds([
                        'table' => 'pages_non_http',
                        'ids' => [
                            'pages_crawler_id' => $this->api['pages'][$url]['data']['db']['pages_crawler_id'],
                            'non_http_id' => $non_http_id,
                        ],
                        'datas' => [
                            'last_crawled' => get_datetime(),
                        ],
                    ], 'update');

                }
            }

        }

        return $a_tags;
    }

    private function isNotSelf($href, $url)
    {
        $api = $this->api['pages'][$url]['data'];

        if ($href !== $url &&
            $href !== $api['canonical']) {
            foreach ($api['hreflang'] as $hreflang => $hreflang_href) {
                if ($href == $hreflang_href) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    private function getATagsData($a_tags, $url)
    {
        $a_tags_counts = [];

        foreach ($a_tags as $a_tag_key => $a_tag_data) {
            array_push($a_tags_counts, $a_tag_data['href']);
        }

        $a_tags_counts = array_count_values($a_tags_counts);

        $a_tags_data = [];
        foreach ($a_tags_counts as $href => $count) {

            $href_info = get_url_info($href);

            if ($href_info !== false) {

                $data = $this->getATagData($href, $a_tags);
                unset($data[$href]['href']);

                $is_follow = true;
                if ($data[$href]['follow'] !== 'follow') {
                    $is_follow = false;
                }

                $is_intern = true;
                if ($this->isExtern($href)) {
                    $is_intern = false;
                }

                $is_page = true;
                $filetype = get_filetype($href);
                if ($filetype !== 'page') {
                    $is_page = false;
                }

                if ($is_page) {
                    if ($is_intern) {
                        for ($i = 0; $i < $count; $i++) {
                            array_push($this->urls['intern'], $href);
                        }
                        if ($is_follow) {
                            for ($i = 0; $i < $count; $i++) {
                                array_push($this->urls['intern_follow'], $href);
                            }
                        }
                    } else {
                        for ($i = 0; $i < $count; $i++) {
                            array_push($this->urls['extern'], $href);
                        }
                        if ($is_follow) {
                            for ($i = 0; $i < $count; $i++) {
                                array_push($this->urls['extern_follow'], $href);
                            }
                        }
                    }
                }

                $domains_id = $this->api['pages'][$url]['data']['db']['domains_id'];
                if ($is_intern !== true) {
                    $ids = $this->db->dataByDomain($href);
                    $domains_id = $ids['domains_id'];
                }

                $is_not_blocked = $this->isNotIgnored($href);
                if ($this->db->isNotBlacklistedDomain($href) == false ||
                    $this->isNotBlocked($href) == false) {
                    $is_not_blocked = false;
                }

                $temp = parse_url($href);
                if ( ! isset($temp['path'])) {
                    $temp['path'] = '/';
                }

                if ( ! isset($temp['scheme'])) {
                    $temp2 = parse_url($url);
                    $temp['scheme'] = $temp2['scheme'];
                }

                $query = '';
                if (isset($temp['query'])) {
                    $query = '?' . $temp['query'];
                }

                $prots_id = $this->db->defaults['prots'][$temp['scheme']]['id'];

                $ruris_id = $this->db->dataByValues([
                    'table' => 'ruris',
                    'datas' => [
                        'slug' => $temp['path'].$query,
                    ],
                ], 'update');

                $pages_crawler_id = $this->api['pages'][$url]['data']['db']['pages_crawler_id'];
                $pages_a_tags_id = $this->db->dataByIds([
                    'table' => 'pages_a_tags',
                    'ids' => [
                        'pages_crawler_id' => $pages_crawler_id,
                        'domains_id' => $domains_id,
                        'ruris_id' => $ruris_id,
                        'prots_id' => $prots_id,
                    ],
                    'datas' => [
                        'last_crawled' => get_datetime(),
                        'href' => $href,
                        'count' => $count,
                        'filetype' => $filetype,
                        'is_page' => $is_page,
                        'is_intern' => $is_intern,
                        'is_follow' => $is_follow,
                        'is_not_blocked' => $is_not_blocked,
                        'anchor' => json_encode($data[$href]['anchor']),
                        'title' => json_encode($data[$href]['title']),
                        'lang' => json_encode($data[$href]['lang']),
                        'hreflang' => json_encode($data[$href]['hreflang']),
                        'anchor_img' => json_encode($data[$href]['anchor_img']),
                    ],
                ], 'update');

                $new_data = [
                    'pages_a_tags_id' => $pages_a_tags_id,
                    'domains_id' => $domains_id,
                    'ruris_id' => $ruris_id,
                    'prots_id' => $prots_id,
                    'count' => $count,
                    'filetype' => $filetype,
                    'is_page' => $is_page,
                    'is_intern' => $is_intern,
                    'is_follow' => $is_follow,
                    'is_not_done' => $this->isNotDone($href),
                    'is_not_ignored' => $this->isNotIgnored($href),
                    'is_not_blocked' => $is_not_blocked,
                    'lang' => $data[$href]['lang'],
                    'hreflang' => $data[$href]['hreflang'],
                    'anchor' => $data[$href]['anchor'],
                    'title' => $data[$href]['title'],
                    'anchor_img' => $data[$href]['anchor_img'],
                ];

                $a_tags_data[$href] = $new_data;
            }
        }

        return $a_tags_data;
    }

    private function getATagData($href, $a_tags)
    {
        $data_temp = [];

        foreach ($a_tags as $a_tag_key => $a_tag_data) {
            if ($a_tag_data['href'] == $href) {
                array_push($data_temp, $a_tag_data);
            }
        }

        $output = [];
        foreach ($data_temp as $key => $data) {

            $href = $data['href'];

            $anchor = '';
            if (isset($data['anchor'])) {
                $anchor = $data['anchor'];
            }
            $title = '';
            if (isset($data['title'])) {
                $title = $data['title'];
            }
            $lang = '';
            if (isset($data['lang'])) {
                $lang = $data['lang'];
            }
            $hreflang = '';
            if (isset($data['hreflang'])) {
                $hreflang = $data['hreflang'];
            }
            $anchor_img = '';
            if (isset($data['anchor_img'])) {
                $anchor_img = $data['anchor_img'];
            }

            if ( ! isset($output[$href])) {
                $output[$href] = [
                    'follow' => 'nofollow',
                    'anchor' => [$anchor],
                    'title' => [$title],
                    'lang' => [$lang],
                    'hreflang' => [$hreflang],
                    'anchor_img' => [$anchor_img],
                ];
            } else {
                array_push($output[$href]['anchor'], $anchor);
                array_push($output[$href]['title'], $title);
                array_push($output[$href]['lang'], $lang);
                array_push($output[$href]['hreflang'], $hreflang);
                array_push($output[$href]['anchor_img'], $anchor_img);
            }

            if ($data['follow'] == 'follow') {
                $output[$href]['follow'] = 'follow';
            }
        }

        return $output;
    }

    private function getATagsCount($a_tags)
    {
        $count = 0;

        foreach ($a_tags as $href => $data) {
            if ($data['is_page']) {
                $count += $data['count'];
            }
        }

        return $count;
    }

    private function getUrlClean($url, $current_url)
    {
        $url_tolower = strtolower($url);

        if ($url_tolower == substr(strtolower($this->api['domain']['url']), 0, -1)) {
            $url = $this->api['domain']['url'];
        }

        if ( substr($url_tolower, 0, 7) !== 'http://' &&
             substr($url_tolower, 0, 8) !== 'https://' ) {

            if (substr($url_tolower, 0, 2) == '//') {

                $url = $this->api['domain']['protocol'] . ':' . $url;


            } else if ( substr($url_tolower, 0, 1) == '/' ) {

                $url = substr($this->api['domain']['url'], 0, -1) . $url;

            } else {

                $base = $this->api['pages'][$current_url]['data']['base'];

                if ( substr($current_url, -1, 1) == '/' ) {

                    if ($base !== '' && $url !== '') {
                        $url = $base . $url;
                    } else {
                        $url = $current_url . $url;
                    }


                } else {

                    if ($url !== '') {
                        $url = $base . $url;
                    } else {
                        $url = $current_url;
                    }

                }

            }

        }

        $wrong_http = 'http';
        if (strtolower($this->api['domain']['protocol']) == 'http') {
            $wrong_http = 'https';
        }
        if ( strpos(strtolower($url), '://' . strtolower($this->api['domain']['name'])) !== false ) {
            $url = str_replace($wrong_http . '://', $this->api['domain']['protocol'] . '://', $url);
        }

        if ( substr_count(strtolower($url), '://' . strtolower($this->api['domain']['name'])) >= 2 ) {
            $url = $this->getUrlWithDomain($url);
        }

        if ( strpos($url, '#') !== false ) {
            $temp = @explode('#', $url);
            $url = $temp[0];
        }

        return $url;
    }

    private function getUrlWithDomain($url)
    {
        $wrong_http = 'http';
        if (strtolower($this->api['domain']['protocol']) == 'http') {
            $wrong_http = 'https';
        }

        $url = str_replace(
            $wrong_http . '://' . $this->api['domain']['name'],
            $this->api['domain']['protocol'] . '://' . $this->api['domain']['name'],
            $url
        );

        return $url;
    }

    private function getBaseTag($doc)
    {
        $base = $this->api['domain']['url'];
        $bases = [];

        foreach ($doc->getElementsByTagName('base') as $base) {
            array_push($bases, $base->getAttribute('href'));
        }

        if ( ! empty($bases) ) {
            $base = end($bases);
        }

        return $base;
    }

    private function getCanonicalTag($doc, $url)
    {
        $output = '';

        $canonicals = [];
        foreach ($doc->getElementsByTagName('link') as $link) {
            if (strtolower($link->getAttribute('rel')) == 'canonical') {
                array_push($canonicals, $this->getUrlClean($link->getAttribute('href'), $url));
            }
        }

        if ( ! empty($canonicals) ) {

            $canonical = trim(end($canonicals));

            if ($canonical !== $url &&
                $canonical !== '') {
                $this->api['notices']['non_canonical'][$url] = $canonical;

                $pages_crawler_id = $this->api['pages'][$url]['data']['db']['pages_crawler_id'];
                $this->db->dataByIds([
                    'table' => 'pages_non_canonical',
                    'ids' => [
                        'pages_crawler_id' => $pages_crawler_id,
                    ],
                    'datas' => [
                        'last_crawled' => get_datetime(),
                        'canonical' => $canonical,
                    ],
                ], 'update');

                $output = $canonical;
            } else {
                $output = $url;
            }

        } else {
            $output = $url;
        }

        return $output;
    }

    private function isNotDone($url)
    {
        if ( ! in_array($url, $this->urls['done']) ) {
            return true;
        }
        return false;
    }

    private function isNotIgnored($url)
    {
        if ($this->isNotIgnoredParameter($url) &&
            $this->isNotIgnoredPath($url) &&
            $this->isNotIgnoredUrl($url)) {
            return true;
        }

        return false;
    }

    private function isNotIgnoredParameter($url)
    {
        if ( ! empty($this->ignore['parameters']) ) {
            $url = strtolower($url);
            foreach ($this->ignore['parameters'] as $param) {
                $param = strtolower($param);
                if ( strpos($url, '?' . $param . '=') !== false ) {
                    return false;
                } else if ( strpos($url, '&' . $param . '=') !== false ) {
                    return false;
                } else if ( strpos($url, '&amp;' . $param . '=') !== false ) {
                    return false;
                } else if ( strpos($url, '?' . $param . '[]=') !== false ) {
                    return false;
                } else if ( strpos($url, '&' . $param . '[]=') !== false ) {
                    return false;
                } else if ( strpos($url, '&amp;' . $param . '[]=') !== false ) {
                    return false;
                }
            }
        }

        return true;
    }

    private function isNotIgnoredPath($url)
    {
        if ( ! empty($this->ignore['pathes']) ) {
            $url = strtolower($url);
            foreach ($this->ignore['pathes'] as $ignore) {
                if ( strpos($url, $ignore) !== false ) {
                    return false;
                }
            }
        }

        return true;
    }

    private function isNotIgnoredUrl($url)
    {
        if ( ! empty($this->ignore['urls']) ) {
            $url = strtolower($url);
            foreach ($this->ignore['urls'] as $ignore) {
                if ( $url == $ignore ) {
                    return false;
                }
            }
        }

        return true;
    }

    private function isNotBlocked($url)
    {
        if ($this->robotsTxt->isOkToCrawl($url, $this->robotsTxtUserAgentName)) {
            return true;
        }

        return false;
    }

    private function setDomain($url)
    {
        $temp = @explode('://', $url);
        $this->api['domain']['protocol'] = $temp[0];
        $last = '';
        if ( substr_count($temp[1], '/') == 0 ) {
            $last = '/';
        }
        $temp = @explode('/', $temp[1] . $last);
        $this->api['domain']['name'] = $temp[0];

        $this->api['domain']['url'] = $this->api['domain']['protocol'] . '://' . $this->api['domain']['name'] . '/';

        $this->api['domain']['info'] = get_domain_info($url);

        $this->api['domain']['db'] = $this->db->dataByDomain($url);

        $this->api['domain']['time']['datetime_start'] = get_datetime($this->config['datetime_format']);
    }

    private function setRobotsTxtUserAgent()
    {
        if ($this->curl->config['user_agent'] == 'mobile') {
            $this->robotsTxtUserAgent = $this->config['robots_txt_user_agent']['mobile'];
        } else if ($this->curl->config['user_agent'] == 'desktop') {
            $this->robotsTxtUserAgent = $this->config['robots_txt_user_agent']['desktop'];
        }
        $this->robotsTxtUserAgentName = $this->config['robots_txt_user_agent']['name'];
    }

    private function setRobotsTxt()
    {
        $url = $this->api['domain']['url'] . 'robots.txt';
        $this->setRobotsTxtUserAgent();

        $data = $this->curl->getContents([$url], $this->robotsTxtUserAgent);

        $code = 'User-agent: *';
        if ($data[$url]['status'] == 200 &&
            strpos(strtolower($data[$url]['content_type']), 'text/plain') !== false) {
            $code = $data[$url]['body'];
        }

        $robots_txt_id = $this->db->dataByValues([
            'table' => 'robots_txt',
            'datas' => [
                'code' => $code,
            ],
        ]);
        $this->api['domain']['db']['robots_txt_id'] = $robots_txt_id;

        $ids = [];
        $domains_id = $this->api['domain']['db']['domains_id'];
        if ($domains_id == 0) {
            $ids = $this->db->dataByDomain($this->api['domain']['url']);
            $domains_id = $ids['domains_id'];
        }
        $this->api['domain']['db']['domains_id'] = $domains_id;

        $domains_crawler_id = $this->db->dataByIds([
            'table' => 'domains_crawler',
            'ids' => [
                'domains_id' => $this->api['domain']['db']['domains_id'],
                'prots_id' => $this->api['domain']['db']['prots_id'],
                'robots_txt_id' => $this->api['domain']['db']['robots_txt_id'],
            ],
        ], 'update');

        $this->api['domain']['db']['domains_crawler_id'] = (int)$domains_crawler_id;
        $this->db->dataByIds([
            'table' => 'domains_crawler',
            'ids' => [
                'id' => $domains_crawler_id,
            ],
            'datas' => [
                'domains_url' => $this->api['domain']['url'],
                'status' => 'start',
                'crawl_firsttime' => get_datetime(),
                'crawl_start' => get_datetime(),
            ],
        ], 'update');

        $robots_txt = new RobotsTxt($code, $this->robotsTxtUserAgentName);
        $this->robotsTxt = $robots_txt;
    }

    private function isExtern($url)
    {
        if (substr(
                strtolower($url),
                0,
                strlen(strtolower($this->api['domain']['url']))
            ) !== strtolower($this->api['domain']['url'])) {
            return true;
        }
        return false;
    }

    private function isHttp($url)
    {
        $is_http = true;

        if ( ! in_array($url, $this->urls['done']) &&
            substr($url, 0, 8) !== 'https://' &&
            substr($url, 0, 7) !== 'http://' &&
            strpos($url, ':') !== false) {
            $is_http = false;
        }

        return $is_http;
    }

    private function setLoadTime()
    {
        $loadtime = round(
            ($this->timings['load_stop'] - $this->timings['load_start']),
            2
        );

        $this->timings['load'] = $loadtime;
    }

    private function setLoadTimeStart()
    {
        $this->timings['load_start'] =
            get_microtime();
    }

    private function setLoadTimeStop()
    {
        $this->timings['load_stop'] =
            get_microtime();

        $this->setLoadTime();
    }

    private function setDb($db)
    {
        if ( ! empty($db)) {
            $this->db = $db;
        } else {
            prexit('No Database!');
        }
    }

    private function setCurl($curl)
    {
        if ( ! empty($curl) ) {
            $this->curl = $curl;
        } else {
            prexit('No Curl!');
        }
    }

    private function setIgnore($ignore)
    {
        if ( isset($ignore['parameters']) ) {
            $this->ignore['parameters'] = $ignore['parameters'];
        }

        if ( isset($ignore['ignore']['pathes']) ) {
            $this->ignore['pathes'] = $ignore['pathes'];
        }

        if ( isset($ignore['urls']) ) {
            $this->ignore['urls'] = $ignore['urls'];
        }
    }

    private function setConfig($config)
    {
        if ( isset($config['max_loops']) ) {
            $this->config['max_loops'] = $config['max_loops'];
        }

        if ( isset($config['do_loops']) ) {
            $this->config['do_loops'] = $config['do_loops'];
        }

        if ( isset($config['datetime_format']) ) {
            $this->config['datetime_format'] = $config['datetime_format'];
        }

        if ( isset($config['robots_txt_user_agent']) ) {
            $this->config['robots_txt_user_agent'] = $config['robots_txt_user_agent'];
        }
    }
}

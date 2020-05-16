<?php

////////////////////////////////////////////////////////////////////////////////

if ( ! function_exists('is_ip') ) {
    function is_ip($domain)
    {
        $temp = parse_url($domain);
        if (isset($temp['path']) && ! isset($temp['host'])) {
            $domain = $temp['path'];
        }
        if (isset($temp['host'])) {
            $domain = $temp['host'];
        }

        $domain = str_replace(['.', ':'], '', $domain);

        if ( ! is_numeric($domain)) {
            return false;
        }

        return true;
    }
}

if ( ! function_exists('get_domain_info') ) {
    function get_domain_info($url)
    {
        $parse = parse_url($url);
        $host = $parse['host'];

        $protocol = $parse['scheme'];
        $domain = '';
        $tld = '';

        if (is_ip($host)) {
            $domain = $host;
        } else {
            if (substr_count($host, '.') == 0) {
                $domain = $host;
            } else {
                $temp = explode('.', $host);
                $tld = '.' . end($temp);
                if (substr_count($tld, '?') >= 1) {
                    $temp = explode('?', $tld);
                    $tld = $temp[0];
                }
                if (substr_count($tld, '#') >= 1) {
                    $temp = explode('#', $tld);
                    $tld = $temp[0];
                }
                if (substr_count($tld, ',') >= 1) {
                    $temp = explode(',', $tld);
                    $tld = $temp[0];
                }
                $tld_len = strlen($tld) - 1;
                $domain = substr($host, 0, -($tld_len));
            }
        }

        return [
            'protocol' => $protocol,
            'domain' => $domain,
            'tld' => $tld,
        ];
    }
}

if ( ! function_exists('get_url_info') ) {
    function get_url_info($url)
    {
        if (substr($url, 0, 2) == '//') {
            $url = 'http:' . $url;
        }

        $parse_url = parse_url($url);

        if (isset($parse_url['scheme'])) {
            $scheme = strtolower($parse_url['scheme']);
            if ($scheme == 'https' || $scheme == 'http') {
                if (isset($parse_url['host'])) {
                    $host = strtolower($parse_url['host']);
                    if (preg_match("/[^a-zA-Z0-9\.\-]/is", $host) !== 1 || LOCAL !== false) {
                        if ( ! is_numeric(str_replace(['.', ':'], '', $host)) || LOCAL !== false) {
                            if (substr_count($host, '.') >= 1 || LOCAL !== false) {
                                if ( ! isset($parse_url['port']) || LOCAL !== false) {

                                    $path = '/';
                                    if (isset($parse_url['path'])) {
                                        $path = $parse_url['path'];
                                    }

                                    $is_www = false;
                                    if (substr($host, 0, 4) == 'www.') {
                                        $is_www = true;
                                    }

                                    $query = '';
                                    if (isset($parse_url['query'])) {
                                        $query = '?' . $parse_url['query'];
                                    }

                                    $temp = explode('.', $host);
                                    $tld = '.' . end($temp);
                                    $tld_len = strlen($tld);

                                    $is_root = false;
                                    if (substr_count($host, '.') == 1) {
                                        $is_root = true;
                                    }

                                    $name = substr($host, 0, -$tld_len) . '.';

                                    return [
                                        'is_root' => $is_root,
                                        'is_www' => $is_www,
                                        'prot' => $scheme,
                                        'name' => $name,
                                        'tld' => $tld,
                                        'ruri' => $path . $query,
                                    ];

                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }
}

if ( ! function_exists('get_absolute_url_by_last_url') ) {
    function get_absolute_url_by_last_url($url, $last_url)
    {
        $parse_url = parse_url($url);
        $parse_last_url = parse_url($last_url);

        if ( ! isset($parse_url['host'])
            && isset($parse_url['path'])) {

            $query = '';
            if (isset($parse_url['query'])) {
                $query = '?' . $parse_url['query'];
            }

            $path = $parse_url['path'];
            if ($path[0] !== '/') {
                $path = '/' . $path;
            }

            $url =
                $parse_last_url['scheme'] . '://' .
                $parse_last_url['host'] .
                $path . $query;
        }

        if ( ! isset($parse_url['path'])) {
            $url = $url . '/';
        }

        return $url;
    }
}

if ( ! function_exists('split_domain') ) {
    function split_domain($domain)
    {
        $output = [];

        $c = 0;
        foreach (explode('.', strrev($domain)) as $n) {
            array_push($output, $n);
            $c++;
        }

        return $output;
    }
}

////////////////////////////////////////////////////////////////////////////////

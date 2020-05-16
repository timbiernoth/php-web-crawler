<?php

////////////////////////////////////////////////////////////////////////////////

if ( ! function_exists('get_ruri') ) {
    function get_ruri($url)
    {
        $temp = parse_url($url);

        $path = '/';
        if (isset($temp['path'])) {
            $path = $temp['path'];
        }

        $query = '';
        if (isset($temp['query'])) {
            $query = '?' . $temp['query'];
        }

        return $path.$query;
    }
}

if ( ! function_exists('get_gzip') ) {
    function get_gzip($headers)
    {
        foreach ($headers as $header) {
            $header = trim(strtolower($header));
            if (substr_count($header, 'content-encoding') >=  1) {
                $temp = explode(':', $header);
                if (substr_count($temp[1], 'gzip') >= 1 ||
                    substr_count($temp[1], 'deflate') >= 1) {
                    return true;
                }
            }
        }

        return false;
    }
}

////////////////////////////////////////////////////////////////////////////////

if ( ! function_exists('get_filetype') ) {
    function get_filetype($url)
    {
        $end = '';
        $temp = @explode('://', $url);
        if ( substr_count($temp[1], '/') == 0 ) {
            $end = '/';
        }
        $temp = @explode('/', $temp[1].$end);
        $url = end($temp);

        $return = '';
        $fileformat = '';

        if ( strpos($url, '?') !== false ) {
            $temp = explode('?', $url);
            $url = $temp[0];
        }

        if ( strpos($url, '.') !== false ) {
            $temp = explode('.', $url);
            $fileformat = strtolower(end($temp));
        }

        if ($fileformat !== '') {

            if (isset(FILEFORMATS[$fileformat])) {

                if (FILEFORMATS[$fileformat]['type'] == 'page') {
                    $return = 'page';
                }

                if ($return == '') {
                    if (FILEFORMATS[$fileformat]['type'] == 'image') {
                        $return = 'image';
                    }
                }

                if ($return == '') {
                    if (FILEFORMATS[$fileformat]['type'] == 'feed') {
                        $return = 'feed';
                    }
                }

                if ($return == '') {
                    if (FILEFORMATS[$fileformat]['type'] == 'pdf') {
                        $return = 'pdf';
                    }
                }

                if ($return == '') {
                    if (FILEFORMATS[$fileformat]['type'] == 'video') {
                        $return = 'video';
                    }
                }

                if ($return == '') {
                    if (FILEFORMATS[$fileformat]['type'] == 'audio') {
                        $return = 'audio';
                    }
                }

                if ($return == '') {
                    if (FILEFORMATS[$fileformat]['type'] == 'vector') {
                        $return = 'vector';
                    }
                }

                if ($return == '') {
                    if (FILEFORMATS[$fileformat]['type'] == 'doc') {
                        $return = 'doc';
                    }
                }

                if ($return == '') {
                    if (FILEFORMATS[$fileformat]['type'] == 'txt') {
                        $return = 'txt';
                    }
                }

                if ($return == '') {
                    if (FILEFORMATS[$fileformat]['type'] == 'flash') {
                        $return = 'flash';
                    }
                }
            }

            if ($return == '') {
                $return = 'page';
            }

        } else {
            $return = 'page';
        }

        return $return;
    }
}

////////////////////////////////////////////////////////////////////////////////

if ( ! function_exists('get_code') ) {
    function get_code($xml)
    {
        $xml = str_replace(["\t", '    '], '', $xml);
        if (function_exists('tidy_repair_string')) {
            $xml = tidy_repair_string($xml);
        }

        return $xml;
    }
}

if ( ! function_exists('get_document') ) {
    function get_document($xml)
    {
        $doc = new DOMDocument;
        @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $xml);

        return $doc;
    }
}

if ( ! function_exists('get_document_body') ) {
    function get_document_body($html)
    {
        $doc = get_document($html);
        $xpath = new DOMXpath($doc);

        $body = '';
        foreach ($xpath->evaluate('//body/node()') as $node) {
            $body .= $doc->saveHtml($node);
        }

        if ( strpos($body, '<h1') !== false ) {

            $strip_by_h1 = @explode('<h1', $body);
            $body = str_replace($strip_by_h1[0], '', $body);
            $body = $body . ' ' . $strip_by_h1[0];

        } else if ( strpos($body, '<h2') !== false ) {

            $strip_by_h2 = @explode('<h2', $body);
            $body = str_replace($strip_by_h2[0], '', $body);
            $body = $body . ' ' . $strip_by_h2[0];

        }

        $body = '<body>' . $body . '</body>';

        return get_document($body);
    }
}

if ( ! function_exists('get_hreflang') ) {
    function get_hreflang($doc)
    {
        $hreflangs = [];

        foreach ($doc->getElementsByTagName('link') as $link) {
            if (strtolower($link->getAttribute('rel')) == 'alternate') {
                if (strtolower($link->getAttribute('hreflang')) !== '') {
                    $hreflangs[$link->getAttribute('hreflang')] = $link->getAttribute('href');
                }
            }
        }

        $hreflangs = array_unique(array_filter($hreflangs));

        return $hreflangs;
    }
}

if ( ! function_exists('get_href_follow') ) {
    function get_href_follow($a)
    {
        $follow = 'follow';
        $rel = strtolower(trim($a->getAttribute('rel')));
        if (strpos($rel, 'nofollow') !== false) {
            $follow = 'nofollow';
        }

        return $follow;
    }
}

if ( ! function_exists('get_inner_html') ) {
    function get_inner_html(DOMNode $tag)
    {
        $children = $tag->childNodes;

        $inner_html = '';
        foreach ($children as $child) {
            $inner_html .= $tag->ownerDocument->saveHTML($child);
        }

        return $inner_html;
    }
}

if ( ! function_exists('get_inner_img') ) {
    function get_inner_img($tag)
    {
        $inner_html = get_inner_html($tag);

        $imgs = [];

        if (substr_count($inner_html, '<img ') >= 1) {
            $doc = get_document($inner_html);
            $c = 0;
            foreach ($doc->getElementsByTagName('img') as $img) {
                $imgs[$c] = [
                    'alt' => trim($img->getAttribute('alt')),
                    'title' => trim($img->getAttribute('title')),
                    'src' => trim($img->getAttribute('src')),
                ];
                $c++;
            }
        }

        return $imgs;
    }
}

if ( ! function_exists('get_hreflang_code') ) {
    function get_hreflang_code($defaults, $code)
    {
        $lang = '';
        $tld = '';

        $code = strtolower(trim(str_replace('_', '-', $code)));
        if (substr_count($code, '-') >= 1) {
            $c = 0;
            foreach (explode('-', $code) as $slug) {
                if ($c == 0) {
                    $lang = $slug;
                } else if ($c == 1) {
                    $tld = '.' . $slug;
                }
                $c++;
            }
        } else {
            $lang = $code;
        }

        $langs_id = 1;
        if (isset($defaults['langs'][$lang])) {
            $langs_id = $defaults['langs'][$lang]['id'];
        }

        $tlds_id = 1;
        if (isset($defaults['tlds'][$tld])) {
            $tlds_id = $defaults['tlds'][$tld]['id'];
        }

        return [
            'code' => $code,
            'langs_id' => $langs_id,
            'tlds_id' => $tlds_id,
        ];
    }
}

////////////////////////////////////////////////////////////////////////////////

if ( ! function_exists('is_not_nofollow_page') ) {
    function is_not_nofollow_page($doc)
    {
        foreach ($doc->getELementsByTagName('meta') as $meta) {
            if (strtolower($meta->getAttribute('name')) == 'robots') {
                $content = strtolower($meta->getAttribute('content'));
                if (strpos($content, 'nofollow') !== false ||
                    strpos($content, 'noindex') !== false) {
                    return false;
                }
            }
        }

        return true;
    }
}

if ( ! function_exists('is_final_url') ) {
    function is_final_url($url, $final_url)
    {
        if ($url == $final_url) {
            return true;
        }

        return false;
    }
}

if ( ! function_exists('is_html') ) {
    function is_html($type)
    {
        if (strpos(strtolower($type), 'text/html') !== false) {
            return true;
        }

        return false;
    }
}

if ( ! function_exists('is_200') ) {
    function is_200($status)
    {
        if ($status == 200) {
            return true;
        }

        return false;
    }
}

////////////////////////////////////////////////////////////////////////////////

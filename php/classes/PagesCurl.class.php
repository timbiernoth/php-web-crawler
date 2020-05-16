<?php

class PagesCurl
{
    public $status = '';

    public $urls = [];

    public $userAgent = '';

    public $config = [
        'max_threads' => 10,
        'user_agent' => 'mobile',
        'connect_timeout' => 5,
        'timeout' => 5,
        'max_redirects' => 5,
        'ssl_verify_host' => 2,
        'ssl_verify_peer' => true,
        'header' => true,
        'nobody' => false,
        'return_transfer' => true,
        'follow_location' => true,
        'auto_referer' => true,
        'fresh_connect' => true,
        'encoding' => '',
        'datetime_format' => 'Y-m-d H:i:s',
    ];

    public function __construct($config = [])
    {
        $this->setConfig($config);
        $this->setUserAgent();
    }

    public function getContents($urls, $ua = '', $login = '')
    {
        if ($ua == '') {
            $this->status = 'running';
        }

        $threads = 0;
        $master = curl_multi_init();

        $curl_opts = $this->getConfig();

        $results = array();

        $count = 0;
        foreach ($urls as $url) {

            array_push($this->urls, $url);

            $ch = curl_init();
            $curl_opts[CURLOPT_URL] = $url;

            if ($ua !== '') {
                $curl_opts[CURLOPT_USERAGENT] = $ua;
            }

            if ($login !== '') {
                $curl_opts[CURLOPT_USERPWD] = $login;
            }

            curl_setopt_array($ch, $curl_opts);
            curl_multi_add_handle($master, $ch);

            $results[$count] = [
                'url' => $url,
                'handle' => $ch,
                'datetime' => get_datetime($this->config['datetime_format']),
            ];

            $count++;
            $threads++;

            if ($threads >= $this->config['max_threads']) {

                while ($threads >= $this->config['max_threads']) {

                    usleep(100);

                    while (($execrun = curl_multi_exec($master, $running)) === -1){}

                    curl_multi_select($master);

                    while ($done = curl_multi_info_read($master)) {

                        foreach ($results as &$res) {

                            if ($res['handle'] == $done['handle']) {

                                $code = curl_multi_getcontent($done['handle']);
                                $info = curl_getinfo($done['handle']);

                                $header_size = curl_getinfo($done['handle'], CURLINFO_HEADER_SIZE);
                                $header = substr($code, 0, $header_size);
                                $body = substr($code, $header_size);
                                $headers = [];
                                foreach (@explode("\n", $header) as $header_key => $header_value) {
                                    $headers[$header_key] = $header_value;
                                }
                                $headers = array_filter(array_map('trim', array_unique($headers)));

                                $res['status'] = curl_getinfo($done['handle'], CURLINFO_HTTP_CODE);
                                $res['final_url'] = curl_getinfo($done['handle'], CURLINFO_EFFECTIVE_URL);
                                $res['content_type'] = curl_getinfo($done['handle'], CURLINFO_CONTENT_TYPE);
                                $res['content_size'] = strlen($code);
                                $res['ip'] = curl_getinfo($done['handle'], CURLINFO_PRIMARY_IP);
                                $res['ttfb'] = $info['starttransfer_time'];
                                $res['headers'] = $headers;
                                $res['body'] = $body;

                            }

                        }

                        curl_multi_remove_handle($master, $done['handle']);
                        curl_close($done['handle']);

                        $threads--;
                    }
                }

            }
        }

        do {

            usleep(100);

            while (($execrun = curl_multi_exec($master, $running)) === -1){}

            curl_multi_select($master);

            while ($done = curl_multi_info_read($master)) {

                foreach ($results as &$res) {

                    if ($res['handle'] == $done['handle']) {

                        $code = curl_multi_getcontent($done['handle']);
                        $info = curl_getinfo($done['handle']);

                        $header_size = curl_getinfo($done['handle'], CURLINFO_HEADER_SIZE);
                        $header = substr($code, 0, $header_size);
                        $body = substr($code, $header_size);
                        $headers = [];
                        foreach (@explode("\n", $header) as $header_key => $header_value) {
                            $headers[$header_key] = $header_value;
                        }
                        $headers = array_filter(array_map('trim', array_unique($headers)));

                        $res['status'] = curl_getinfo($done['handle'], CURLINFO_HTTP_CODE);
                        $res['final_url'] = curl_getinfo($done['handle'], CURLINFO_EFFECTIVE_URL);
                        $res['content_type'] = curl_getinfo($done['handle'], CURLINFO_CONTENT_TYPE);
                        $res['content_size'] = strlen($code);
                        $res['ip'] = curl_getinfo($done['handle'], CURLINFO_PRIMARY_IP);
                        $res['ttfb'] = $info['starttransfer_time'];
                        $res['headers'] = $headers;
                        $res['body'] = $body;

                    }

                }

                curl_multi_remove_handle($master, $done['handle']);
                curl_close($done['handle']);

                $threads--;
            }

        } while($running > 0);

        curl_multi_close($master);

        $output = [];
        foreach ($results as $key => $data) {
            $url = $data['url'];
            unset($data['url']);
            $output[$url] = $data;
        }
        unset($results);

        if ($ua == '') {
            $this->status = 'done';
        }

        return $output;
    }

    private function getConfig()
    {
        return [
            CURLOPT_SSL_VERIFYHOST => $this->config['ssl_verify_host'],
            CURLOPT_SSL_VERIFYPEER => $this->config['ssl_verify_peer'],
            CURLOPT_CONNECTTIMEOUT => $this->config['connect_timeout'],
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_HEADER => $this->config['header'],
            CURLOPT_NOBODY => $this->config['nobody'],
            CURLOPT_RETURNTRANSFER => $this->config['return_transfer'],
            CURLOPT_FOLLOWLOCATION => $this->config['follow_location'],
            CURLOPT_MAXREDIRS => $this->config['max_redirects'],
            CURLOPT_AUTOREFERER => $this->config['auto_referer'],
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_FRESH_CONNECT => $this->config['fresh_connect'],
            CURLOPT_ENCODING => $this->config['encoding'],
        ];
    }

    private function getDatetime()
    {
        $date = new DateTime();
        return $date->format($this->config['datetime_format']);
    }

    private function setUserAgent()
    {
        $user_agent = '';

        if ($this->config['user_agent'] == 'mobile') {
            $user_agent = 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5';
        } else if ($this->config['user_agent'] == 'desktop') {
            $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:62.0) Gecko/20100101 Firefox/62.0';
        } else {
            $user_agent = $this->config['user_agent'];
        }

        $this->userAgent = $user_agent;
    }

    private function setConfig($config)
    {
        if ( isset($config['max_threads']) ) {
            $this->config['max_threads'] = $config['max_threads'];
        }

        if ( isset($config['user_agent']) ) {
            $this->config['user_agent'] = $config['user_agent'];
        }

        if ( isset($config['connect_timeout']) ) {
            $this->config['connect_timeout'] = $config['connect_timeout'];
        }

        if ( isset($config['timeout']) ) {
            $this->config['timeout'] = $config['timeout'];
        }

        if ( isset($config['max_redirects']) ) {
            $this->config['max_redirects'] = $config['max_redirects'];
        }

        if ( isset($config['ssl_verify_host']) ) {
            $this->config['ssl_verify_host'] = $config['ssl_verify_host'];
        }

        if ( isset($config['ssl_verify_peer']) ) {
            $this->config['ssl_verify_peer'] = $config['ssl_verify_peer'];
        }

        if ( isset($config['header']) ) {
            $this->config['header'] = $config['header'];
        }

        if ( isset($config['nobody']) ) {
            $this->config['nobody'] = $config['nobody'];
        }

        if ( isset($config['return_transfer']) ) {
            $this->config['return_transfer'] = $config['return_transfer'];
        }

        if ( isset($config['follow_location']) ) {
            $this->config['follow_location'] = $config['follow_location'];
        }

        if ( isset($config['auto_referer']) ) {
            $this->config['auto_referer'] = $config['auto_referer'];
        }

        if ( isset($config['fresh_connect']) ) {
            $this->config['fresh_connect'] = $config['fresh_connect'];
        }

        if ( isset($config['encoding']) ) {
            $this->config['encoding'] = $config['encoding'];
        }

        if ( isset($config['datetime_format']) ) {
            $this->config['datetime_format'] = $config['datetime_format'];
        }
    }
}

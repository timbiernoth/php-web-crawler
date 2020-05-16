<?php

class DomainsCurl
{
    private $options = [];

    public function __construct($options = [])
    {
        $this->options = $options;
    }

    public function getContents($urls)
    {
        $curly = [];
        $result = [];

        $mh = curl_multi_init();

        foreach ($urls as $id => $d) {

            $curly[$id] = curl_init();

            $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
            curl_setopt($curly[$id], CURLOPT_URL, $url);
            curl_setopt($curly[$id], CURLOPT_HEADER, CONFIG_DOMAINS_CURL['header']);
            curl_setopt($curly[$id], CURLOPT_SSL_VERIFYHOST, CONFIG_DOMAINS_CURL['ssl_verify_host']);
            curl_setopt($curly[$id], CURLOPT_SSL_VERIFYPEER, CONFIG_DOMAINS_CURL['ssl_verify_peer']);
            curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, CONFIG_DOMAINS_CURL['return_transfer']);
            curl_setopt($curly[$id], CURLOPT_USERPWD, CONFIG_DOMAINS_CURL['userpwd']);

            if (is_array($d)) {
                if ( ! empty($d['post'])) {
                    curl_setopt($curly[$id], CURLOPT_POST, 1);
                    curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
                }
            }

            if ( ! empty($this->options)) {
                curl_setopt_array($curly[$id], $this->options);
            }

            curl_multi_add_handle($mh, $curly[$id]);
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        foreach ($curly as $id => $c) {
            $result[$id] = curl_multi_getcontent($c);
            curl_multi_remove_handle($mh, $c);
        }

        curl_multi_close($mh);

        return $result;
    }

}
